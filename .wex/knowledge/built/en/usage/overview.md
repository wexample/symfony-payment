## Paying

```php
$payment = $paymentService->getOrCreateForPayable($cart, 'card');   // reuses the open one, replaces it if the amount changed
$initiation = $paymentService->initiate($payment);                 // client secret or redirect; null for a zero amount
```

Routes (import `src/Resources/config/routes.yaml`):

- `POST /_payment/webhook/{provider}`: provider notifications, verified by the provider's `WebhookParserInterface`; replays are harmless;
- `GET /_payment/status/{id}?token=…`: the status only, for the browser waiting on a payment page; the token comes from `PaymentAccessService::createToken()`.

`confirmManualPayment()` records a received transfer or cash payment; `refund()` refunds through the provider, `recordRefund()` records one made elsewhere. While payments may still be confirmed by a provider, `bin/console check:run deployment` (symfony-check) fails, so a deployment can wait for them.

## Events

`PaymentStatusChangedEvent` on every change, then `PaymentSucceededEvent` (exactly once), `PaymentFailedEvent`, `PaymentCanceledEvent`, `PaymentRefundedEvent`. A package owning payables provides a `PayableResolverInterface` so listeners get the payable back.
