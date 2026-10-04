<?php

namespace Wexample\SymfonyPayment\Event;

/**
 * Dispatched exactly once per payment, when the money is collected.
 * Carts, invoices and memberships complete themselves on it.
 */
class PaymentSucceededEvent extends AbstractPaymentEvent
{
}
