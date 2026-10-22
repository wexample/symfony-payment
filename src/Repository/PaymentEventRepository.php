<?php

namespace Wexample\SymfonyPayment\Repository;

use Wexample\SymfonyHelpers\Repository\AbstractRepository;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Entity\PaymentEvent;

/**
 * @method PaymentEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method PaymentEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method PaymentEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentEventRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return PaymentEvent::class;
    }

    /**
     * @return PaymentEvent[] Oldest first.
     */
    public function findForPayment(Payment $payment): array
    {
        return $this->findBy(['payment' => $payment], ['dateCreated' => 'ASC']);
    }
}
