<?php

namespace Wexample\SymfonyPayment\Service;

use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPayment\Interface\PayableResolverInterface;

/**
 * Finds the payable behind a payment, through the resolvers each package provides.
 */
class PayableRegistry
{
    /**
     * @param iterable<PayableResolverInterface> $resolvers
     */
    public function __construct(
        private readonly iterable $resolvers = [],
    ) {
    }

    public function resolve(Payment $payment): ?PayableInterface
    {
        $type = $payment->getPayableType();
        $id = $payment->getPayableId();

        if (null === $type || null === $id) {
            return null;
        }

        foreach ($this->resolvers as $resolver) {
            if ($resolver->supports($type)) {
                return $resolver->resolve($type, $id);
            }
        }

        return null;
    }
}
