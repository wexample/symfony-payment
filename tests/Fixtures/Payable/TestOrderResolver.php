<?php

namespace Wexample\SymfonyPayment\Tests\Fixtures\Payable;

use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPayment\Interface\PayableResolverInterface;

class TestOrderResolver implements PayableResolverInterface
{
    /** @var array<string, TestOrder> */
    public array $orders = [];

    public function supports(string $payableType): bool
    {
        return TestOrder::getPayableType() === $payableType;
    }

    public function resolve(string $payableType, string $payableId): ?PayableInterface
    {
        return $this->orders[$payableId] ?? null;
    }
}
