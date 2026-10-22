<?php

namespace Wexample\SymfonyPayment\Interface;

/**
 * Something that can be paid: a cart, an invoice, a membership.
 * The payment only keeps its type and id, so it never depends on the payable's package.
 */
interface PayableInterface
{
    /**
     * Short stable name of the kind of payable ("cart", "invoice").
     */
    public static function getPayableType(): string;

    public function getPayableId(): string;

    /**
     * What is due now, in minor units.
     */
    public function getPayableAmount(): int;

    public function getPayableCurrencyCode(): string;

    public function getPayableDescription(): ?string;
}
