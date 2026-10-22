## Architecture

src/Enum/PaymentStatus.php declares the allowed transitions; src/Service/PaymentService.php is the only place that moves a payment: a forbidden transition throws, the same status is a no-op returning false (which is what makes webhooks idempotent). Providers are found in the `symfony-remote-payment` registry by method; src/Class/ManualPaymentGateway.php is one of them, for money that arrives outside any provider.

The package never knows carts or invoices: a payment holds a payable type and id, and the payable's package listens to the events.
