<?php

namespace Wexample\SymfonyPayment\Repository;

use DateTimeImmutable;
use Doctrine\ORM\QueryBuilder;
use Wexample\SymfonyHelpers\Repository\AbstractRepository;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Enum\PaymentStatus;

/**
 * @method Payment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Payment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Payment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PaymentRepository extends AbstractRepository
{
    public static function getEntityClassName(): string
    {
        return Payment::class;
    }

    public function findOneByProviderReference(string $providerReference): ?Payment
    {
        return $this->findOneBy(['providerReference' => $providerReference]);
    }

    /**
     * @return Payment[] Newest first.
     */
    public function findByPayable(
        string $payableType,
        string $payableId
    ): array {
        return $this->findBy(
            ['payableType' => $payableType, 'payableId' => $payableId],
            ['dateCreated' => self::SORT_DESC]
        );
    }

    public function findOpenForPayable(
        string $payableType,
        string $payableId
    ): ?Payment {
        foreach ($this->findByPayable($payableType, $payableId) as $payment) {
            if ($payment->getStatus()->isOpen()) {
                return $payment;
            }
        }

        return null;
    }

    /**
     * Payments a provider may still confirm: pending or processing, updated recently.
     * Stopping the application should wait for them (webhooks would hit a stopped app).
     */
    public function countInFlight(int $minutes = 30): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.status IN (:statuses)')
            ->andWhere('p.dateUpdated >= :since')
            ->setParameter('statuses', [PaymentStatus::Pending->value, PaymentStatus::Processing->value])
            ->setParameter('since', new DateTimeImmutable('-'.$minutes.' minutes'))
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * The payments a list shows, newest first unless sorted otherwise: of one
     * owner, or of everybody; in some statuses, through one provider, or
     * holding some words in what describes them.
     *
     * @param list<string> $statuses
     */
    public function queryForList(
        ?string $ownerReference = null,
        array $statuses = [],
        ?string $provider = null,
        ?string $search = null,
    ): QueryBuilder {
        $builder = $this->createQueryBuilder('p')
            ->orderBy('p.dateCreated', self::SORT_DESC);

        if (null !== $ownerReference) {
            $builder->andWhere('p.ownerReference = :owner')->setParameter('owner', $ownerReference);
        }

        if ($statuses) {
            $builder->andWhere('p.status IN (:statuses)')->setParameter('statuses', $statuses);
        }

        if ($provider) {
            $builder->andWhere('p.provider = :provider')->setParameter('provider', $provider);
        }

        if ($search) {
            $builder
                ->andWhere('LOWER(p.description) LIKE :search OR LOWER(p.customerEmail) LIKE :search OR LOWER(p.providerReference) LIKE :search')
                ->setParameter('search', '%'.mb_strtolower($search).'%');
        }

        return $builder;
    }
}
