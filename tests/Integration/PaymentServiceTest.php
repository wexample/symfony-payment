<?php

namespace Wexample\SymfonyPayment\Tests\Integration;

use Wexample\SymfonyCheck\Class\Shutdown;
use Wexample\SymfonyCheck\Service\CheckService;
use Wexample\SymfonyMedia\Entity\Media;
use Wexample\SymfonyMedia\Service\MediaStorage;
use Wexample\SymfonyPayment\Checker\ShutdownPaymentChecker;
use Wexample\SymfonyPayment\Enum\PaymentStatus;
use Wexample\SymfonyPayment\Event\PaymentFailedEvent;
use Wexample\SymfonyPayment\Event\PaymentProofAttachedEvent;
use Wexample\SymfonyPayment\Event\PaymentRefundedEvent;
use Wexample\SymfonyPayment\Event\PaymentSucceededEvent;
use Wexample\SymfonyPayment\Exception\PaymentTransitionException;
use Wexample\SymfonyPayment\Repository\PaymentEventRepository;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PayableRegistry;
use Wexample\SymfonyPayment\Tests\Fixtures\Payable\TestOrder;
use Wexample\SymfonyPayment\Tests\Fixtures\Payable\TestOrderResolver;

class PaymentServiceTest extends AbstractPaymentTestCase
{
    public function testSuccessIsRefusedBeforePending(): void
    {
        $payment = $this->getPaymentService()->create(1000, 'EUR', 'card');

        $this->expectException(PaymentTransitionException::class);
        $this->getPaymentService()->markSucceeded($payment);
    }

    public function testProviderFlowIsIdempotent(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->getOrCreateForPayable(new TestOrder('o1', 2500), 'card');
        $initiation = $service->initiate($payment);

        $this->assertSame(PaymentStatus::Pending, $payment->getStatus());
        $this->assertSame('fake', $payment->getProvider());
        $this->assertSame($initiation->providerReference, $payment->getProviderReference());
        $this->assertSame((string) $payment->getId(), $this->getProvider()->requests[0]->reference);

        $notification = $this->getProvider()->succeed($payment->getProviderReference());
        $service->handleNotification($notification);

        $this->assertSame(PaymentStatus::Succeeded, $payment->getStatus());
        $this->assertNotNull($payment->getDatePaid());
        $this->assertSame(1, $this->getEvents()->count(PaymentSucceededEvent::class));

        // A replayed notification changes nothing.
        $service->handleNotification($notification);
        $this->assertSame(1, $this->getEvents()->count(PaymentSucceededEvent::class));

        // A failure after success is a bug, not a state.
        $this->expectException(PaymentTransitionException::class);
        $service->handleNotification($this->getProvider()->fail($payment->getProviderReference()));
    }

    public function testFailureThenRetry(): void
    {
        $service = $this->getPaymentService();
        $order = new TestOrder('o2', 1000);
        $payment = $service->getOrCreateForPayable($order, 'card');
        $service->initiate($payment);
        $service->handleNotification($this->getProvider()->fail($payment->getProviderReference(), 'Insufficient funds.'));

        $this->assertSame(PaymentStatus::Failed, $payment->getStatus());
        $this->assertSame('Insufficient funds.', $payment->getFailureMessage());
        $this->assertSame(1, $this->getEvents()->count(PaymentFailedEvent::class));

        // A failed payment is still the open one of its payable.
        $this->assertSame($payment, $service->getOrCreateForPayable($order, 'card'));
    }

    public function testAmountChangeReplacesTheOpenPayment(): void
    {
        $service = $this->getPaymentService();
        $order = new TestOrder('o3', 1000);
        $first = $service->getOrCreateForPayable($order, 'card');
        $service->initiate($first);

        $order->amount = 1500;
        $second = $service->getOrCreateForPayable($order, 'card');

        $this->assertNotSame($first, $second);
        $this->assertSame(PaymentStatus::Canceled, $first->getStatus());
        $this->assertSame(1500, $second->getAmount());
    }

    public function testZeroAmountNeverReachesAProvider(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->getOrCreateForPayable(new TestOrder('free', 0), 'card');

        $this->assertNull($service->initiate($payment));
        $this->assertSame(PaymentStatus::Succeeded, $payment->getStatus());
        $this->assertSame([], $this->getProvider()->requests);
        $this->assertSame(1, $this->getEvents()->count(PaymentSucceededEvent::class));
    }

    public function testManualPayment(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->create(5000, 'EUR', 'transfer');

        $service->initiate($payment);
        $this->assertSame('manual', $payment->getProvider());
        $this->assertSame(PaymentStatus::Pending, $payment->getStatus());

        $this->assertTrue($service->confirmManualPayment($payment, new \DateTimeImmutable('2026-03-01'), 'VIR 123'));
        $this->assertSame(PaymentStatus::Succeeded, $payment->getStatus());
        $this->assertSame('2026-03-01', $payment->getDatePaid()->format('Y-m-d'));
        $this->assertSame('VIR 123', $payment->getProviderChargeReference());
    }

    public function testWhatTheProviderSaidIsKept(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->create(2500, 'EUR', 'card');
        $service->initiate($payment);

        $notification = $this->getProvider()->succeed($payment->getProviderReference());
        $service->handleNotification($notification);
        $service->handleNotification($notification);

        $events = static::getContainer()->get(PaymentEventRepository::class)->findForPayment($payment);
        $this->assertCount(2, $events, 'A replay is kept too: the trail shows what the provider sent.');
        $this->assertSame('fake', $events[0]->getProvider());
        $this->assertSame('succeeded', $events[0]->getType());
        $this->assertSame($notification->eventId, $events[0]->getEventId());
    }

    public function testAProofWaitsForTheMoney(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->create(5000, 'EUR', 'transfer');
        $service->attachProof($payment, $this->importProof());

        $this->assertSame('manual', $payment->getProvider());
        $this->assertSame(PaymentStatus::Pending, $payment->getStatus());
        $this->assertTrue($payment->getProof()->isRestricted());
        $this->assertSame(1, $this->getEvents()->count(PaymentProofAttachedEvent::class));

        $service->confirmManualPayment($payment);
        $this->assertSame(PaymentStatus::Succeeded, $payment->getStatus());
    }

    public function testOnlyAManualPaymentTakesAProof(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->create(5000, 'EUR', 'card');

        $this->expectException(\LogicException::class);
        $service->attachProof($payment, $this->importProof());
    }

    private function importProof(): Media
    {
        $file = tempnam(sys_get_temp_dir(), 'proof');
        file_put_contents($file, 'Transfer of 50.00 EUR');

        return static::getContainer()->get(MediaStorage::class)->import($file, 'receipt.txt');
    }

    public function testRefunds(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->create(10000, 'EUR', 'card');
        $service->initiate($payment);
        $service->handleNotification($this->getProvider()->succeed($payment->getProviderReference()));

        $service->refund($payment, 3000, 'Partial');
        $this->assertSame(PaymentStatus::PartiallyRefunded, $payment->getStatus());
        $this->assertSame(7000, $payment->getAmountKept());

        $service->refund($payment);
        $this->assertSame(PaymentStatus::Refunded, $payment->getStatus());
        $this->assertCount(2, $payment->getRefunds());
        $this->assertSame(2, $this->getEvents()->count(PaymentRefundedEvent::class));

        $this->expectException(\LogicException::class);
        $service->refund($payment, 1);
    }

    public function testInFlightAndPayableResolution(): void
    {
        $service = $this->getPaymentService();
        $order = new TestOrder('o9', 700);
        static::getContainer()->get(TestOrderResolver::class)->orders['o9'] = $order;

        $payment = $service->getOrCreateForPayable($order, 'card');
        $service->initiate($payment);

        $this->assertSame(1, static::getContainer()->get(PaymentRepository::class)->countInFlight());
        $this->assertTrue(static::getContainer()->get(CheckService::class)->check(new Shutdown())->has(ShutdownPaymentChecker::CODE));
        $this->assertSame($order, static::getContainer()->get(PayableRegistry::class)->resolve($payment));
        $this->assertSame($order, static::getContainer()->get(PayableRegistry::class)->find(TestOrder::getPayableType(), 'o9'));
        $this->assertNull(static::getContainer()->get(PayableRegistry::class)->find('unknown', 'o9'));
    }
}
