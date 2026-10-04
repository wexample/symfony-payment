<?php

namespace Wexample\SymfonyPayment\Service;

use Wexample\SymfonyPayment\Entity\Payment;

/**
 * Signs payment ids, so that a browser can poll the status of the payment it
 * started without being logged in, and without exposing other payments.
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
}
