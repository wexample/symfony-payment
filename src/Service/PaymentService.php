<?php

namespace Wexample\SymfonyPayment\Service;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Wexample\SymfonyPayment\Class\ManualPaymentGateway;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Entity\PaymentRefund;
use Wexample\SymfonyPayment\Enum\PaymentStatus;
use Wexample\SymfonyPayment\Event\PaymentCanceledEvent;
use Wexample\SymfonyPayment\Event\PaymentFailedEvent;
use Wexample\SymfonyPayment\Event\PaymentRefundedEvent;
use Wexample\SymfonyPayment\Event\PaymentStatusChangedEvent;
use Wexample\SymfonyPayment\Event\PaymentSucceededEvent;
use Wexample\SymfonyPayment\Exception\PaymentTransitionException;
use Wexample\SymfonyPayment\Interface\PayableInterface;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyRemotePayment\Class\PaymentInitiation;
use Wexample\SymfonyRemotePayment\Class\PaymentNotification;
use Wexample\SymfonyRemotePayment\Class\PaymentRequest;
use Wexample\SymfonyRemotePayment\Interface\PaymentGatewayInterface;
use Wexample\SymfonyRemotePayment\Service\PaymentProviderRegistry;

/**
 * The single entry point for payments: creation, provider calls and every status change.
 *
 * Status changes are guarded (PaymentStatus::allowedTransitions()); asking for the
 * status a payment already has is a no-op that returns false and dispatches nothing,
 * so replayed webhooks are harmless.
 */
class PaymentService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly PaymentRepository $paymentRepository,
        private readonly PaymentProviderRegistry $providerRegistry,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function create(
        int $amount,
        string $currencyCode,
        string $method,
        ?PayableInterface $payable = null,
        ?string $description = null,
        ?string $customerEmail = null,
        ?string $ownerReference = null,
    ): Payment {
        $payment = (new Payment())
            ->setAmount($amount)
            ->setCurrencyCode($currencyCode)
            ->setMethod($method)
            ->setDescription($description ?? $payable?->getPayableDescription())
            ->setCustomerEmail($customerEmail)
            ->setOwnerReference($ownerReference);

        if ($payable) {
            $payment->setPayable($payable::getPayableType(), $payable->getPayableId());
        }

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        return $payment;
    }

    /**
     * The open payment of a payable, or a new one. An open payment whose amount
     * no longer matches what is due is canceled and replaced, so that the provider
     * never collects a stale amount.
     */
    public function getOrCreateForPayable(
        PayableInterface $payable,
        string $method,
        ?string $customerEmail = null,
        ?string $ownerReference = null,
    ): Payment {
        $open = $this->paymentRepository->findOpenForPayable($payable::getPayableType(), $payable->getPayableId());

        if ($open) {
            if ($open->getAmount() === $payable->getPayableAmount()
                && $open->getCurrencyCode() === $payable->getPayableCurrencyCode()
                && $open->getMethod() === $method) {
                return $open;
            }

            $this->cancel($open);
        }

        return $this->create(
            amount: $payable->getPayableAmount(),
            currencyCode: $payable->getPayableCurrencyCode(),
            method: $method,
            payable: $payable,
            customerEmail: $customerEmail,
            ownerReference: $ownerReference,
        );
    }

    /**
     * Sends the payment to the provider handling its method. A zero amount
     * never reaches a provider: it succeeds at once.
     */
    public function initiate(
        Payment $payment,
        ?string $returnUrl = null
    ): ?PaymentInitiation {
        if (0 === $payment->getAmount()) {
            $this->transition($payment, PaymentStatus::Pending);
            $this->markSucceeded($payment);

            return null;
        }

        if (PaymentStatus::Pending === $payment->getStatus() && $payment->getProviderReference()) {
            // Already sent; the browser just needs the same secret again.
            return null;
        }

        $gateway = $this->resolveGateway($payment);
        $initiation = $gateway->initiate(new PaymentRequest(
            reference: (string) $payment->getId(),
            amount: $payment->getAmount(),
            currencyCode: $payment->getCurrencyCode(),
            method: $payment->getMethod(),
            description: $payment->getDescription(),
            customerEmail: $payment->getCustomerEmail(),
            returnUrl: $returnUrl,
            metadata: array_filter([
                'payment_id' => (string) $payment->getId(),
                'payable_type' => $payment->getPayableType(),
                'payable_id' => $payment->getPayableId(),
            ]),
            idempotencyKey: 'payment-'.$payment->getId().'-'.$payment->getAmount(),
        ));

        $payment
            ->setProvider($gateway->getName())
            ->setProviderReference($initiation->providerReference)
            ->setClientSecret($initiation->clientSecret);

        $this->transition($payment, PaymentStatus::Pending);
        $this->applyStatus($payment, PaymentStatus::fromProvider($initiation->status));

        return $initiation;
    }

    /**
     * Applies a verified provider notification. Unknown payments are ignored.
     */
    public function handleNotification(PaymentNotification $notification): ?Payment
    {
        $payment = $this->paymentRepository->findOneByProviderReference($notification->providerReference);

        if (! $payment) {
            return null;
        }

        if ($notification->chargeReference) {
            $payment->setProviderChargeReference($notification->chargeReference);
        }

        $this->applyStatus(
            $payment,
            PaymentStatus::fromProvider($notification->status),
            $notification->failureMessage
        );

        return $payment;
    }

    /**
     * Routes a status reported by a provider to the method owning its side effects.
     */
    private function applyStatus(
        Payment $payment,
        PaymentStatus $status,
        ?string $failureMessage = null
    ): bool {
        return match ($status) {
            PaymentStatus::Succeeded => $this->markSucceeded($payment),
            PaymentStatus::Failed => $this->markFailed($payment, $failureMessage),
            PaymentStatus::Canceled => $this->markCanceled($payment),
            PaymentStatus::Pending, PaymentStatus::Processing => $this->transition($payment, $status),
            // Refunds are recorded by refund() / recordRefund(), which know the amount.
            default => false,
        };
    }

    /**
     * Asks the provider for the current state, for when a webhook was missed.
     */
    public function synchronize(Payment $payment): Payment
    {
        if (! $payment->getProviderReference() || ManualPaymentGateway::NAME === $payment->getProvider()) {
            return $payment;
        }

        $remote = $this->providerRegistry->getGateway($payment->getProvider())->fetch($payment->getProviderReference());

        return $this->handleNotification(new PaymentNotification(
            provider: $payment->getProvider(),
            eventId: 'sync-'.uniqid(),
            providerReference: $remote->providerReference,
            status: $remote->status,
            amountReceived: $remote->amountReceived,
            chargeReference: $remote->chargeReference,
            failureMessage: $remote->failureMessage,
        )) ?? $payment;
    }

    public function markSucceeded(
        Payment $payment,
        ?DateTimeImmutable $datePaid = null
    ): bool {
        $previous = $payment->getStatus();

        if (! $this->transition($payment, PaymentStatus::Succeeded, flush: false, dispatch: false)) {
            return false;
        }

        $payment->setDatePaid($datePaid ?? new DateTimeImmutable())->setFailureMessage(null);
        $this->entityManager->flush();
        $this->dispatch($payment, $previous, new PaymentSucceededEvent($payment, $previous));

        return true;
    }

    public function markFailed(
        Payment $payment,
        ?string $message = null
    ): bool {
        $previous = $payment->getStatus();

        if (! $this->transition($payment, PaymentStatus::Failed, flush: false, dispatch: false)) {
            return false;
        }

        $payment->setFailureMessage($message);
        $this->entityManager->flush();
        $this->dispatch($payment, $previous, new PaymentFailedEvent($payment, $previous));

        return true;
    }

    public function markCanceled(Payment $payment): bool
    {
        $previous = $payment->getStatus();

        if (! $this->transition($payment, PaymentStatus::Canceled, dispatch: false)) {
            return false;
        }

        $this->dispatch($payment, $previous, new PaymentCanceledEvent($payment, $previous));

        return true;
    }

    /**
     * Cancels on the provider side too, when the payment was sent there.
     */
    public function cancel(Payment $payment): bool
    {
        if ($payment->getProviderReference()
            && $payment->getProvider()
            && ManualPaymentGateway::NAME !== $payment->getProvider()
            && PaymentStatus::Failed !== $payment->getStatus()) {
            $this->providerRegistry->getGateway($payment->getProvider())->cancel($payment->getProviderReference());
        }

        return $this->markCanceled($payment);
    }

    /**
     * A transfer or cash payment was received.
     */
    public function confirmManualPayment(
        Payment $payment,
        ?DateTimeImmutable $datePaid = null,
        ?string $reference = null
    ): bool {
        if (PaymentStatus::Created === $payment->getStatus()) {
            $this->initiate($payment);
        }

        if ($reference) {
            $payment->setProviderChargeReference($reference);
        }

        return $this->markSucceeded($payment, $datePaid);
    }

    /**
     * Refunds through the provider, then records it.
     */
    public function refund(
        Payment $payment,
        ?int $amount = null,
        ?string $reason = null
    ): PaymentRefund {
        $amount ??= $payment->getAmount() - $payment->getAmountRefunded();
        $this->assertRefundable($payment, $amount);
        $providerReference = null;

        if ($payment->getProvider() && ManualPaymentGateway::NAME !== $payment->getProvider()) {
            $providerReference = $this->providerRegistry
                ->getGateway($payment->getProvider())
                ->refund($payment->getProviderReference(), $amount, $reason)
                ->refundReference;
        }

        return $this->recordRefund($payment, $amount, $providerReference, $reason);
    }

    /**
     * Records a refund made elsewhere (by hand, or seen in the provider dashboard).
     */
    public function recordRefund(
        Payment $payment,
        int $amount,
        ?string $providerReference = null,
        ?string $reason = null
    ): PaymentRefund {
        $this->assertRefundable($payment, $amount);
        $previous = $payment->getStatus();

        $refund = (new PaymentRefund())
            ->setAmount($amount)
            ->setProviderReference($providerReference)
            ->setReason($reason);
        $payment->addRefund($refund);
        $payment->setAmountRefunded($payment->getAmountRefunded() + $amount);
        $this->entityManager->persist($refund);

        $status = $payment->getAmountRefunded() >= $payment->getAmount()
            ? PaymentStatus::Refunded
            : PaymentStatus::PartiallyRefunded;
        $this->guard($payment, $status);
        $payment->setStatus($status);
        $this->entityManager->flush();

        $this->dispatch($payment, $previous, new PaymentRefundedEvent($payment, $previous, $refund));

        return $refund;
    }

    /**
     * Moves the payment to a status. Same status: no-op, false. Forbidden: exception.
     */
    public function transition(
        Payment $payment,
        PaymentStatus $status,
        bool $flush = true,
        bool $dispatch = true
    ): bool {
        $previous = $payment->getStatus();

        if ($previous === $status) {
            return false;
        }

        $this->guard($payment, $status);
        $payment->setStatus($status);

        if ($flush) {
            $this->entityManager->flush();
        }

        if ($dispatch) {
            $this->eventDispatcher->dispatch(new PaymentStatusChangedEvent($payment, $previous));
        }

        return true;
    }

    private function guard(
        Payment $payment,
        PaymentStatus $status
    ): void {
        if (! $payment->getStatus()->canTransitionTo($status)) {
            throw new PaymentTransitionException($payment->getStatus(), $status);
        }
    }

    private function dispatch(
        Payment $payment,
        PaymentStatus $previous,
        object $event
    ): void {
        $this->eventDispatcher->dispatch(new PaymentStatusChangedEvent($payment, $previous));
        $this->eventDispatcher->dispatch($event);
    }

    private function assertRefundable(
        Payment $payment,
        int $amount
    ): void {
        if (! $payment->getStatus()->isPaid()) {
            throw new \LogicException('Only a paid payment can be refunded.');
        }

        if ($amount <= 0 || $amount > $payment->getAmount() - $payment->getAmountRefunded()) {
            throw new \InvalidArgumentException('The refund amount must be positive and at most what is left.');
        }
    }

    private function resolveGateway(Payment $payment): PaymentGatewayInterface
    {
        if ($payment->getProvider()) {
            return $this->providerRegistry->getGateway($payment->getProvider());
        }

        $gateway = $this->providerRegistry->findGatewayForMethod($payment->getMethod());

        if (! $gateway) {
            throw new \RuntimeException(sprintf('No payment gateway handles the method "%s".', $payment->getMethod()));
        }

        return $gateway;
    }
}
