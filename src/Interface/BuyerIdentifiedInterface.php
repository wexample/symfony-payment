<?php

namespace Wexample\SymfonyPayment\Interface;

use Wexample\SymfonyGeo\Entity\AddressSnapshot;

/**
 * A payable that knows who pays it, as they gave themselves at checkout: what an
 * invoice needs to name its customer, and what decides the VAT charged to them.
 */
interface BuyerIdentifiedInterface
{
    public function getBuyerName(): ?string;

    /**
     * Null for a private person.
     */
    public function getBuyerCompanyName(): ?string;

    public function getBuyerEmail(): ?string;

    public function getBuyerPhone(): ?string;

    /**
     * With its country prefix, as typed; unchecked.
     */
    public function getBuyerVatNumber(): ?string;

    /**
     * The billing address, written down as it was given.
     */
    public function getBuyerAddress(): ?AddressSnapshot;
}
