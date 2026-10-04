<?php

namespace Wexample\SymfonyPayment\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;

#[ORM\Entity]
#[ORM\Table(name: 'payment_refund')]
class PaymentRefund extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Payment::class, inversedBy: 'refunds')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Payment $payment;

    #[ORM\Column(type: Types::INTEGER)]
    protected int $amount;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $providerReference = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $reason = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    protected DateTimeImmutable $dateCreated;

    public function __construct()
    {
        parent::__construct();
        $this->dateCreated = new DateTimeImmutable();
    }

    public function getPayment(): Payment
    {
        return $this->payment;
    }

    public function setPayment(Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getAmount(): int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): static
    {
        $this->amount = $amount;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): static
    {
        $this->reason = $reason;

        return $this;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->dateCreated;
    }
}
