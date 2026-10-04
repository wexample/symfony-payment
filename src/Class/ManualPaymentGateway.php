<?php

namespace Wexample\SymfonyPayment\Class;

use Wexample\SymfonyRemotePayment\Class\PaymentInitiation;
use Wexample\SymfonyRemotePayment\Class\PaymentRequest;
use Wexample\SymfonyRemotePayment\Class\ProviderPayment;
use Wexample\SymfonyRemotePayment\Class\ProviderRefund;
use Wexample\SymfonyRemotePayment\Enum\ProviderPaymentStatus;
use Wexample\SymfonyRemotePayment\Interface\PaymentGatewayInterface;

/**
 * Money that arrives outside any provider: bank transfer, cash, cheque.
 * The payment stays pending until someone confirms it with
 * PaymentService::confirmManualPayment().
 */
class ManualPaymentGateway implements PaymentGatewayInterface
{
    public const string NAME = 'manual';

    /**
     * @param list<string> $methods
     */
    public function __construct(
        private readonly array $methods = ['transfer', 'cash', 'cheque', 'other'],
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function supports(string $method): bool
    {
        return in_array($method, $this->methods, true);
    }

    public function initiate(PaymentRequest $request): PaymentInitiation
    {
        return new PaymentInitiation(self::NAME.'_'.$request->reference, ProviderPaymentStatus::Pending);
    }

    public function fetch(string $providerReference): ProviderPayment
    {
        throw new \LogicException('A manual payment has no remote state: confirm it through PaymentService.');
    }

    public function cancel(string $providerReference): ProviderPayment
    {
        throw new \LogicException('Cancel a manual payment through PaymentService.');
    }

    public function refund(
        string $providerReference,
        ?int $amount = null,
        ?string $reason = null
    ): ProviderRefund {
        throw new \LogicException('A manual payment is refunded by hand: record it with PaymentService::recordRefund().');
    }
}
