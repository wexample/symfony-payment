<?php

namespace Wexample\SymfonyPayment\Enum;

use Wexample\SymfonyRemotePayment\Enum\ProviderPaymentStatus;

/**
 * The life of a payment. Every change goes through PaymentService, which only
 * allows the transitions listed in allowedTransitions().
 */
enum PaymentStatus: string
{
    /** Recorded, not sent to any provider yet. */
    case Created = 'created';

    /** Sent to the provider, waiting for the payer. */
    case Pending = 'pending';

    /** Accepted by the provider, money not settled yet. */
    case Processing = 'processing';

    case Succeeded = 'succeeded';

    case Failed = 'failed';

    case Canceled = 'canceled';

    case PartiallyRefunded = 'partially_refunded';

    case Refunded = 'refunded';

    /**
     * @return list<self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Created => [self::Pending, self::Canceled],
            self::Pending => [self::Processing, self::Succeeded, self::Failed, self::Canceled],
            self::Processing => [self::Succeeded, self::Failed, self::Canceled],
            // A failed attempt can be retried.
            self::Failed => [self::Pending, self::Canceled],
            self::Succeeded => [self::PartiallyRefunded, self::Refunded],
            self::PartiallyRefunded => [self::PartiallyRefunded, self::Refunded],
            self::Canceled, self::Refunded => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Still waiting for money: a payable should reuse this payment rather than create another.
     */
    public function isOpen(): bool
    {
        return in_array($this, [self::Created, self::Pending, self::Processing, self::Failed], true);
    }

    public function isPaid(): bool
    {
        return in_array($this, [self::Succeeded, self::PartiallyRefunded, self::Refunded], true);
    }

    public static function fromProvider(ProviderPaymentStatus $status): self
    {
        return match ($status) {
            ProviderPaymentStatus::Pending, ProviderPaymentStatus::RequiresAction => self::Pending,
            ProviderPaymentStatus::Processing => self::Processing,
            ProviderPaymentStatus::Succeeded => self::Succeeded,
            ProviderPaymentStatus::Failed => self::Failed,
            ProviderPaymentStatus::Canceled => self::Canceled,
            ProviderPaymentStatus::Refunded => self::Refunded,
            ProviderPaymentStatus::PartiallyRefunded => self::PartiallyRefunded,
        };
    }
}
