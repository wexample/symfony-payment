<?php

namespace Wexample\SymfonyPayment\Api\Dto;

use Wexample\SymfonyApi\Api\Dto\AbstractEntityDto;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyPayment\Entity\Payment;

/**
 * A payment as its payer and its administrators read it: what, how much, how,
 * where it stands. What only the provider is told — its client secret, the
 * metadata — stays on the server.
 */
class PaymentDto extends AbstractEntityDto
{
    /** In the currency's minor unit, as the payment holds it. */
    public int $amount;

    public int $amountRefunded;

    public string $currencyCode;

    public string $status;

    public string $method;

    public ?string $provider;

    public ?string $providerReference;

    public ?string $description;

    public ?string $customerEmail;

    public ?string $payableType;

    public ?string $payableId;

    public ?string $failureMessage;

    public string $dateCreated;

    public string $dateUpdated;

    public ?string $datePaid;

    /**
     * @param Payment $entity
     */
    public static function fromEntity(AbstractEntity $entity): self
    {
        $dto = parent::fromEntity($entity);

        $dto->amount = $entity->getAmount();
        $dto->amountRefunded = $entity->getAmountRefunded();
        $dto->currencyCode = $entity->getCurrencyCode();
        $dto->status = $entity->getStatus()->value;
        $dto->method = $entity->getMethod();
        $dto->provider = $entity->getProvider();
        $dto->providerReference = $entity->getProviderReference();
        $dto->description = $entity->getDescription();
        $dto->customerEmail = $entity->getCustomerEmail();
        $dto->payableType = $entity->getPayableType();
        $dto->payableId = $entity->getPayableId();
        $dto->failureMessage = $entity->getFailureMessage();
        $dto->dateCreated = $entity->getDateCreated()->format(DATE_ATOM);
        $dto->dateUpdated = $entity->getDateUpdated()->format(DATE_ATOM);
        $dto->datePaid = $entity->getDatePaid()?->format(DATE_ATOM);

        return $dto;
    }
}
