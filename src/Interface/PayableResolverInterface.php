<?php

namespace Wexample\SymfonyPayment\Interface;

/**
 * Loads the payable a payment refers to. Each package owning a payable type
 * provides one, so that payment listeners get the object back.
 */
interface PayableResolverInterface
{
    public function supports(string $payableType): bool;

    public function resolve(
        string $payableType,
        string $payableId
    ): ?PayableInterface;
}
