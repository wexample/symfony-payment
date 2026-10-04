<?php

namespace Wexample\SymfonyPayment\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Enum\PaymentStatus;

abstract class AbstractPaymentEvent extends Event
{
    public function __construct(
        public readonly Payment $payment,
        public readonly PaymentStatus $previousStatus,
    ) {
    }
}
