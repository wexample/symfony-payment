<?php

namespace Wexample\SymfonyPayment\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Wexample\Pseudocode\Attribute\PseudocodeExport;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyPayment\Repository\PaymentEventRepository;

/**
 * What a provider said about a payment, kept as it said it: the trail to read
 * when a payment's status is disputed, never what decides it.
 */
#[PseudocodeExport(inherited: true)]
#[ORM\Entity(repositoryClass: PaymentEventRepository::class)]
#[ORM\Table(name: 'payment_event')]
class PaymentEvent extends AbstractEntity
{
    #[ORM\ManyToOne(targetEntity: Payment::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    protected Payment $payment;

    #[ORM\Column(type: Types::STRING, length: 50)]
    protected string $provider;

    /** The provider's own id of the event, when it gives one. */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    protected ?string $eventId = null;

    /** The status the provider reported: succeeded, failed… */
    #[ORM\Column(type: Types::STRING, length: 50)]
    protected string $type;

    #[ORM\Column(type: Types::JSON)]
    protected array $data = [];

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

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function setProvider(string $provider): static
    {
        $this->provider = $provider;

        return $this;
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function setEventId(?string $eventId): static
    {
        $this->eventId = $eventId;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function getDateCreated(): DateTimeImmutable
    {
        return $this->dateCreated;
    }
}
