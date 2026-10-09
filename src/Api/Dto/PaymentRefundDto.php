<?php

namespace Wexample\SymfonyPayment\Api\Dto;

use Wexample\SymfonyApi\Api\Dto\AbstractEntityDto;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyPayment\Entity\PaymentRefund;

/**
 * A refund of a payment: how much went back, when, and why.
 */
class PaymentRefundDto extends AbstractEntityDto
{
    /** In the currency's minor unit, the payment's. */
    public int $amount;

    public ?string $providerReference;

    public ?string $reason;

    public string $dateCreated;

    /**
     * @param PaymentRefund $entity
     */
    public static function fromEntity(AbstractEntity $entity): self
    {
        $dto = parent::fromEntity($entity);

        $dto->amount = $entity->getAmount();
        $dto->providerReference = $entity->getProviderReference();
        $dto->reason = $entity->getReason();
        $dto->dateCreated = $entity->getDateCreated()->format(DATE_ATOM);

        return $dto;
    }
}
