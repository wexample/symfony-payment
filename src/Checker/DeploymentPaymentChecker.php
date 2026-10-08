<?php

namespace Wexample\SymfonyPayment\Checker;

use Wexample\SymfonyCheck\Class\Deployment;
use Wexample\SymfonyCheck\Class\Finding;
use Wexample\SymfonyCheck\Interface\CheckerInterface;
use Wexample\SymfonyPayment\Repository\PaymentRepository;

/**
 * Holds a release back while payments may still be confirmed by a provider:
 * pending or processing, and touched within the last half hour. Stopping
 * the application then would leave a webhook unanswered.
 */
class DeploymentPaymentChecker implements CheckerInterface
{
    public const string CODE = 'deployment.payments_in_flight';

    public const int MINUTES = 30;

    public function __construct(
        private readonly PaymentRepository $paymentRepository,
    ) {
    }

    public function supports(object $subject): bool
    {
        return $subject instanceof Deployment;
    }

    public function check(object $subject): iterable
    {
        $count = $this->paymentRepository->countInFlight(self::MINUTES);

        if ($count > 0) {
            yield Finding::error(self::CODE, ['count' => $count, 'minutes' => self::MINUTES]);
        }
    }
}
