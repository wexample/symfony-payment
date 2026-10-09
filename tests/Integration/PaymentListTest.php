<?php

namespace Wexample\SymfonyPayment\Tests\Integration;

use Symfony\Component\Security\Core\User\InMemoryUser;
use Wexample\SymfonyPayment\Enum\PaymentStatus;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

/**
 * What the lists of payments read: one payer's own, or everybody's narrowed by
 * status, provider and words.
 */
class PaymentListTest extends AbstractPaymentTestCase
{
    public function testAPayerListsTheirOwnPaymentsOnly(): void
    {
        $service = $this->getPaymentService();
        $service->create(1000, 'EUR', 'card', description: 'Mine', ownerReference: 'alice');
        $service->create(2000, 'EUR', 'card', description: 'Theirs', ownerReference: 'bob');

        $found = $this->repository()->queryForList(ownerReference: 'alice')->getQuery()->getResult();

        $this->assertSame(['Mine'], array_map(static fn ($payment) => $payment->getDescription(), $found));
    }

    public function testEverybodysPaymentsNarrowByStatusProviderAndWords(): void
    {
        $service = $this->getPaymentService();
        $paid = $service->create(1000, 'EUR', 'card', description: 'Annual subscription', customerEmail: 'alice@example.com');
        $service->initiate($paid);
        $service->handleNotification($this->getProvider()->succeed($paid->getProviderReference()));
        $service->create(2000, 'EUR', 'card', description: 'Monthly subscription');

        $repository = $this->repository();

        $this->assertCount(2, $repository->queryForList(search: 'SUBSCRIPTION')->getQuery()->getResult());
        $this->assertCount(1, $repository->queryForList(statuses: [PaymentStatus::Succeeded->value])->getQuery()->getResult());
        $this->assertCount(1, $repository->queryForList(provider: 'fake')->getQuery()->getResult());
        $this->assertCount(1, $repository->queryForList(search: 'alice@')->getQuery()->getResult());
    }

    public function testAUserOwnsWhatCarriesTheirReference(): void
    {
        $access = static::getContainer()->get(PaymentAccessService::class);
        $payment = $this->getPaymentService()->create(1000, 'EUR', 'card', ownerReference: 'alice');

        // A user without an id is known by their identifier.
        $this->assertSame('alice', $access->ownerReferenceOf(new InMemoryUser('alice', null)));
        $this->assertTrue($access->isOwnedBy($payment, new InMemoryUser('alice', null)));
        $this->assertFalse($access->isOwnedBy($payment, new InMemoryUser('bob', null)));
        $this->assertFalse($access->isOwnedBy($payment, null));
    }

    private function repository(): PaymentRepository
    {
        return static::getContainer()->get(PaymentRepository::class);
    }
}
