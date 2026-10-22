<?php

namespace Wexample\SymfonyPayment\Event;

use Wexample\SymfonyPayment\Entity\Payment;

/**
 * The payer of a manual payment sent what shows they paid. The payment stays
 * pending: only PaymentService::confirmManualPayment() says the money arrived.
 */
class PaymentProofAttachedEvent extends AbstractPaymentEvent
{
    public function __construct(Payment $payment)
    {
        parent::__construct($payment, $payment->getStatus());
    }
}
