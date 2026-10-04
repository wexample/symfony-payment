<?php

namespace Wexample\SymfonyPayment\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PaymentAccessService;
use Wexample\SymfonyPayment\Service\PaymentService;
use Wexample\SymfonyRemotePayment\Exception\InvalidWebhookException;
use Wexample\SymfonyRemotePayment\Service\PaymentProviderRegistry;

/**
 * Machine endpoints: the browser polling a status, providers posting webhooks.
 */
#[Route(path: '/_payment', name: 'payment_')]
class PaymentController
{
    public function __construct(
        private readonly PaymentRepository $paymentRepository,
        private readonly PaymentService $paymentService,
        private readonly PaymentAccessService $paymentAccessService,
        private readonly PaymentProviderRegistry $providerRegistry,
    ) {
    }

    /**
     * Status only: the payment state is driven by webhooks, never by the browser.
     */
    #[Route(path: '/status/{id}', name: 'status', methods: ['GET'])]
    public function status(
        string $id,
        Request $request
    ): JsonResponse {
        $payment = Uuid::isValid($id) ? $this->paymentRepository->find(Uuid::fromString($id)) : null;

        if (! $payment || ! $this->paymentAccessService->isTokenValid($payment, $request->query->get('token'))) {
            return new JsonResponse(['error' => 'not_found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => $payment->getStatus()->value]);
    }

    #[Route(path: '/webhook/{provider}', name: 'webhook', methods: ['POST'])]
    public function webhook(
        string $provider,
        Request $request
    ): Response {
        if (! $this->providerRegistry->has($provider, \Wexample\SymfonyRemotePayment\Interface\WebhookParserInterface::class)) {
            return new Response('', Response::HTTP_NOT_FOUND);
        }

        $headers = [];
        foreach ($request->headers->all() as $name => $values) {
            $headers[strtolower($name)] = (string) ($values[0] ?? '');
        }

        try {
            $notification = $this->providerRegistry
                ->getWebhookParser($provider)
                ->parseWebhook($request->getContent(), $headers);
        } catch (InvalidWebhookException) {
            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        if ($notification) {
            $this->paymentService->handleNotification($notification);
        }

        // Any verified event is acknowledged, even unknown or replayed ones,
        // so that the provider stops retrying.
        return new Response('', Response::HTTP_OK);
    }
}
