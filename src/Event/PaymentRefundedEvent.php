<?php

namespace Wexample\SymfonyPayment\Event;

use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Entity\PaymentRefund;
use Wexample\SymfonyPayment\Enum\PaymentStatus;

/**
 * Dispatched for each refund, partial or full.
 */
class PaymentRefundedEvent extends AbstractPaymentEvent
{
    public function __construct(
        Payment $payment,
        PaymentStatus $previousStatus,
        public readonly PaymentRefund $refund,
    ) {
        parent::__construct($payment, $previousStatus);
    }
}
