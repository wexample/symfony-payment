<?php

namespace Wexample\SymfonyPayment\Tests\Integration;

use Symfony\Component\HttpFoundation\Request;
use Wexample\SymfonyPayment\Enum\PaymentStatus;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

class PaymentControllerTest extends AbstractPaymentTestCase
{
    public function testStatusNeedsTheToken(): void
    {
        $payment = $this->getPaymentService()->create(1000, 'EUR', 'card');
        $token = static::getContainer()->get(PaymentAccessService::class)->createToken($payment);
        $kernel = static::$kernel;

        $response = $kernel->handle(Request::create('/_payment/status/'.$payment->getId().'?token='.$token));
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(['status' => 'created'], json_decode($response->getContent(), true));

        $response = $kernel->handle(Request::create('/_payment/status/'.$payment->getId().'?token=wrong'));
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testWebhook(): void
    {
        $service = $this->getPaymentService();
        $payment = $service->create(1000, 'EUR', 'card');
        $service->initiate($payment);
        $payload = json_encode(['eventId' => 'evt_1', 'providerReference' => $payment->getProviderReference(), 'status' => 'succeeded']);

        $bad = Request::create('/_payment/webhook/fake', 'POST', content: $payload);
        $this->assertSame(400, static::$kernel->handle($bad)->getStatusCode());

        $good = Request::create('/_payment/webhook/fake', 'POST', server: ['HTTP_X_FAKE_SIGNATURE' => 'valid'], content: $payload);
        $this->assertSame(200, static::$kernel->handle($good)->getStatusCode());

        // The kernel resets its services between requests: read the payment again.
        $stored = static::getContainer()->get(PaymentRepository::class)->find($payment->getId());
        $this->assertSame(PaymentStatus::Succeeded, $stored->getStatus());

        $this->assertSame(404, static::$kernel->handle(Request::create('/_payment/webhook/nope', 'POST'))->getStatusCode());
    }
}
