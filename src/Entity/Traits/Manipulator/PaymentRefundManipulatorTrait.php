<?php

namespace Wexample\SymfonyPayment\Entity\Traits\Manipulator;

use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;
use Wexample\SymfonyPayment\Entity\PaymentRefund;

trait PaymentRefundManipulatorTrait
{
    use EntityManipulatorTrait;

    public static function getEntityClassName(): string
    {
        return PaymentRefund::class;
    }
}
