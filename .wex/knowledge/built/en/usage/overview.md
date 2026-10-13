## Paying

```php
$payment = $paymentService->getOrCreateForPayable($cart, 'card');   // reuses the open one, replaces it if the amount changed
$initiation = $paymentService->initiate($payment);                 // client secret or redirect; null for a zero amount
```

Routes (import `src/Resources/config/routes.yaml`):

- `POST /_payment/webhook/{provider}`: provider notifications, verified by the provider's `WebhookParserInterface`; replays are harmless;
- `GET /_payment/status/{id}?token=…`: the status only, for the browser waiting on a payment page; the token comes from `PaymentAccessService::createToken()`.

`confirmManualPayment()` records a received transfer or cash payment; `refund()` refunds through the provider, `recordRefund()` records one made elsewhere. While payments may still be confirmed by a provider, `bin/console check:run shutdown` (symfony-check) fails, so whoever stops the application — a release, a maintenance — can wait for them.

## Manual payments and their proof

A transfer, a cheque or cash goes through the `manual` gateway: the payment stays pending until someone confirms the money arrived. Meanwhile the payer may send what shows they paid:

```php
$proof = $mediaStorage->import($uploadedPath, 'receipt.pdf');   // symfony-media
$paymentService->attachProof($payment, $proof);                  // restricted, PaymentProofAttachedEvent
$paymentService->confirmManualPayment($payment, $dateReceived, 'VIR 123');
```

The proof is a `Media` of the library, restricted: read through grants only. Only a pending manual payment takes one.

## What providers said

Every verified notification is kept as a `PaymentEvent` — provider, event id, reported status and the raw data — replays included. It is the trail to read when a status is disputed; the status itself is only changed by `PaymentService`.

## Buyers

A payable that knows who pays it implements `BuyerIdentifiedInterface`: name, company, email, phone, VAT number and billing address, as given at checkout. Nothing here reads it; a package invoicing payables (symfony-accounting) does, without knowing the payable's own class.

## Events

`PaymentStatusChangedEvent` on every change, then `PaymentSucceededEvent` (exactly once), `PaymentFailedEvent`, `PaymentCanceledEvent`, `PaymentRefundedEvent`; `PaymentProofAttachedEvent` when a proof arrives, the status unchanged. A package owning payables provides a `PayableResolverInterface` so listeners get the payable back.
