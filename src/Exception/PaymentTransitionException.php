<?php

namespace Wexample\SymfonyPayment\Exception;

use Wexample\SymfonyPayment\Enum\PaymentStatus;

class PaymentTransitionException extends \LogicException
{
    public function __construct(
        public readonly PaymentStatus $from,
        public readonly PaymentStatus $to,
    ) {
        parent::__construct(sprintf('A payment cannot go from "%s" to "%s".', $from->value, $to->value));
    }
}
