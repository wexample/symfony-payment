<?php

namespace Wexample\SymfonyPayment\Service;

use Symfony\Component\Security\Core\User\UserInterface;
use Wexample\SymfonyPayment\Entity\Payment;

/**
 * Who may read a payment: the browser that started it, through a signed id —
 * polling the status without being logged in, without exposing the others —,
 * and the user who pays, through the payment's owner reference.
 */
class PaymentAccessService
{
    public function __construct(
        private readonly string $secret,
    ) {
    }

    public function createToken(Payment $payment): string
    {
        return hash_hmac('sha256', 'payment:'.$payment->getId(), $this->secret);
    }

    public function isTokenValid(
        Payment $payment,
        ?string $token
    ): bool {
        return null !== $token && hash_equals($this->createToken($payment), $token);
    }

    /**
     * The owner reference a user's payments carry: the host's identifier of
     * the payer — their id, as a cart keeps it —, or their user identifier
     * when they have none.
     */
    public function ownerReferenceOf(?UserInterface $user): ?string
    {
        if (null === $user) {
            return null;
        }

        return method_exists($user, 'getId') && null !== $user->getId()
            ? (string) $user->getId()
            : $user->getUserIdentifier();
    }

    public function isOwnedBy(
        Payment $payment,
        ?UserInterface $user
    ): bool {
        $reference = $this->ownerReferenceOf($user);

        return null !== $reference && $payment->getOwnerReference() === $reference;
    }
}
