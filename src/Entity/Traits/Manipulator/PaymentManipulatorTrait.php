<?php

namespace Wexample\SymfonyPayment\Entity\Traits\Manipulator;

use Wexample\SymfonyHelpers\Entity\Traits\Manipulator\EntityManipulatorTrait;
use Wexample\SymfonyPayment\Entity\Payment;

trait PaymentManipulatorTrait
{
    use EntityManipulatorTrait;

    public static function getEntityClassName(): string
    {
        return Payment::class;
    }
}
