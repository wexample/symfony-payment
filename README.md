# symfony-payment

Version: 3.0.0

## Paying

```php
$payment = $paymentService->getOrCreateForPayable($cart, 'card');   // reuses the open one, replaces it if the amount changed
$initiation = $paymentService->initiate($payment);                 // client secret or redirect; null for a zero amount
```

Routes (import `src/Resources/config/routes.yaml`):

- `POST /_payment/webhook/{provider}`: provider notifications, verified by the provider's `WebhookParserInterface`; replays are harmless;
- `GET /_payment/status/{id}?token=…`: the status only, for the browser waiting on a payment page; the token comes from `PaymentAccessService::createToken()`.

`confirmManualPayment()` records a received transfer or cash payment; `refund()` refunds through the provider, `recordRefund()` records one made elsewhere. While payments may still be confirmed by a provider, `bin/console check:run shutdown` (symfony-check) fails, so whoever stops the application — a release, a maintenance — can wait for them.

## Events

`PaymentStatusChangedEvent` on every change, then `PaymentSucceededEvent` (exactly once), `PaymentFailedEvent`, `PaymentCanceledEvent`, `PaymentRefundedEvent`. A package owning payables provides a `PayableResolverInterface` so listeners get the payable back.

## Table of Contents

- [Paying](#paying)
- [Events](#events)
- [Architecture](#architecture)
- [Integration in the Suite](#integration-in-the-suite)
- [Dependencies](#dependencies)
- [Versioning & Compatibility Policy](#versioning--compatibility-policy)
- [License](#license)
- [About us](#about-us)
- [Migration Notes](#migration-notes)

## Architecture

src/Enum/PaymentStatus.php declares the allowed transitions; src/Service/PaymentService.php is the only place that moves a payment: a forbidden transition throws, the same status is a no-op returning false (which is what makes webhooks idempotent). Providers are found in the `symfony-remote-payment` registry by method; src/Class/ManualPaymentGateway.php is one of them, for money that arrives outside any provider.

The package never knows carts or invoices: a payment holds a payable type and id, and the payable's package listens to the events.

## Integration in the Suite

This package is part of the Wexample Suite — a collection of high-quality, modular tools designed to work seamlessly together across multiple languages and environments.

### Related Packages

The suite includes packages for configuration management, file handling, prompts, and more. Each package can be used independently or as part of the integrated suite.

Visit the [Wexample Suite documentation](https://docs.wexample.com) for the complete package ecosystem.

## Dependencies

- php: >=8.5
- wexample/symfony-check: >=2.0.0
- wexample/symfony-helpers: >=15.0.0
- wexample/symfony-money: >=5.0.0
- wexample/symfony-remote-payment: >=2.0.0

## Versioning & Compatibility Policy

Wexample packages follow **Semantic Versioning** (SemVer):

- **MAJOR**: Breaking changes
- **MINOR**: New features, backward compatible
- **PATCH**: Bug fixes, backward compatible

We maintain backward compatibility within major versions and provide clear migration guides for breaking changes.

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

Free to use in both personal and commercial projects.

## About us

[Wexample](https://wexample.com) stands as a cornerstone of the digital ecosystem — a collective of seasoned engineers, researchers, and creators driven by a relentless pursuit of technological excellence. More than a media platform, it has grown into a vibrant community where innovation meets craftsmanship, and where every line of code reflects a commitment to clarity, durability, and shared intelligence.

This packages suite embodies this spirit. Trusted by professionals and enthusiasts alike, it delivers a consistent, high-quality foundation for modern development — open, elegant, and battle-tested. Its reputation is built on years of collaboration, refinement, and rigorous attention to detail, making it a natural choice for those who demand both robustness and beauty in their tools.

Wexample cultivates a culture of mastery. Each package, each contribution carries the mark of a community that values precision, ethics, and innovation — a community proud to shape the future of digital craftsmanship.

## Migration Notes

When upgrading between major versions, refer to the migration guides in the documentation.

Breaking changes are clearly documented with upgrade paths and examples.
