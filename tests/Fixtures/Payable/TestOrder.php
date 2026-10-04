<?php

namespace Wexample\SymfonyPayment\Tests\Fixtures\Payable;

use Wexample\SymfonyPayment\Interface\PayableInterface;

class TestOrder implements PayableInterface
{
    public function __construct(
        public string $id,
        public int $amount,
    ) {
    }

    public static function getPayableType(): string
    {
        return 'test_order';
    }

    public function getPayableId(): string
    {
        return $this->id;
    }

    public function getPayableAmount(): int
    {
        return $this->amount;
    }

    public function getPayableCurrencyCode(): string
    {
        return 'EUR';
    }

    public function getPayableDescription(): ?string
    {
        return 'Order '.$this->id;
    }
}
