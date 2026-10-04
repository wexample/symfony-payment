<?php

namespace Wexample\SymfonyPayment\Entity;

use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyMoney\Entity\Traits\HasPriceCurrencyTrait;
use Wexample\SymfonyPayment\Enum\PaymentStatus;
use Wexample\SymfonyPayment\Repository\PaymentRepository;

/**
 * One attempt to collect an amount for a payable, through one provider.
 *
 * The status is only changed by PaymentService (see PaymentStatus for the allowed
 * transitions); setters here are for the service, not for controllers.
 */
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
#[ORM\Index(columns: ['payable_type', 'payable_id'])]
#[ORM\Index(columns: ['status', 'date_updated'])]
class Payment extends AbstractEntity
{
    use HasPriceCurrencyTrait;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $amount;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $amountRefunded = 0;

    #[ORM\Column(type: Types::STRING, length: 30, enumType: PaymentStatus::class)]
    protected PaymentStatus $status = PaymentStatus::Created;

    /** card, sepa_debit, transfer, cash… */
    #[ORM\Column(type: Types::STRING, length: 50)]
    protected string $method;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    protected ?string $provider = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, nullable: true)]
    protected ?string $providerReference = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $providerChargeReference = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $clientSecret = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    protected ?string $payableType = null;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    protected ?string $payableId = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $description = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $customerEmail = null;

    /** Free identifier of the payer in the host app (user id), for access checks. */
    #[ORM\Column(type: Types::STRING, length: 64, nullable: true)]
    protected ?string $ownerReference = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $dateCreated;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $dateUpdated;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?DateTimeImmutable $datePaid = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $failureMessage = null;

    #[ORM\Column(type: Types::JSON)]
    protected array $metadata = [];

    /** @var Collection<int, PaymentRefund> */
    #[ORM\OneToMany(targetEntity: PaymentRefund::class, mappedBy: 'payment', cascade: ['persist'])]
    protected Collection $refunds;

    public function __construct()
    {
        parent::__construct();
        $this->dateCreated = new DateTimeImmutable();
        $this->dateUpdated = $this->dateCreated;
        $this->refunds = new ArrayCollection();
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        if ($amount < 0) {
            throw new \InvalidArgumentException('A payment amount cannot be negative.');
        }

        $this->amount = $amount;

        return $this;
    }

    public function getAmountRefunded(): int
    {
        return $this->amountRefunded;
    }

    public function setAmountRefunded(int $amountRefunded): static
    {
        $this->amountRefunded = $amountRefunded;

        return $this;
    }

    public function getAmountKept(): int
    {
        return $this->status->isPaid() ? $this->amount - $this->amountRefunded : 0;
    }

    public function getStatus(): PaymentStatus
    {
        return $this->status;
    }

    /**
     * @internal Use PaymentService, which guards transitions and dispatches events.
     */
    public function setStatus(PaymentStatus $status): static
    {
        $this->status = $status;
        $this->dateUpdated = new DateTimeImmutable();

        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function getProvider(): ?string
    {
        return $this->provider;
    }

    public function setProvider(?string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getProviderReference(): ?string
    {
        return $this->providerReference;
    }

    public function setProviderReference(?string $providerReference): static
    {
        $this->providerReference = $providerReference;

        return $this;
    }

    public function getProviderChargeReference(): ?string
    {
        return $this->providerChargeReference;
    }

    public function setProviderChargeReference(?string $providerChargeReference): static
    {
        $this->providerChargeReference = $providerChargeReference;

        return $this;
    }

    public function getClientSecret(): ?string
    {
        return $this->clientSecret;
    }

    public function setClientSecret(?string $clientSecret): static
    {
        $this->clientSecret = $clientSecret;

        return $this;
    }

    public function getPayableType(): ?string
    {
        return $this->payableType;
    }

    public function getPayableId(): ?string
    {
        return $this->payableId;
    }

    public function setPayable(
        ?string $payableType,
        ?string $payableId
    ): static {
        $this->payableType = $payableType;
        $this->payableId = $payableId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getCustomerEmail(): ?string
    {
        return $this->customerEmail;
    }

    public function setCustomerEmail(?string $customerEmail): static
    {
        $this->customerEmail = $customerEmail;

        return $this;
    }

    public function getOwnerReference(): ?string
    {
        return $this->ownerReference;
    }

    public function setOwnerReference(?string $ownerReference): static
    {
        $this->ownerReference = $ownerReference;

        return $this;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->dateCreated;
    }

    public function setDateCreated(DateTimeImmutable $dateCreated): static
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateUpdated(): DateTimeImmutable
    {
        return $this->dateUpdated;
    }

    public function getDatePaid(): ?DateTimeImmutable
    {
        return $this->datePaid;
    }

    public function setDatePaid(?DateTimeImmutable $datePaid): static
    {
        $this->datePaid = $datePaid;

        return $this;
    }

    public function getFailureMessage(): ?string
    {
        return $this->failureMessage;
    }

    public function setFailureMessage(?string $failureMessage): static
    {
        $this->failureMessage = $failureMessage;

        return $this;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }

    public function setMetadata(array $metadata): static
    {
        $this->metadata = $metadata;

        return $this;
    }

    /**
     * @return Collection<int, PaymentRefund>
     */
    public function getRefunds(): Collection
    {
        return $this->refunds;
    }

    public function addRefund(PaymentRefund $refund): static
    {
        if (! $this->refunds->contains($refund)) {
            $this->refunds->add($refund);
            $refund->setPayment($this);
        }

        return $this;
    }
}
