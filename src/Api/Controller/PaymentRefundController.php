<?php

namespace Wexample\SymfonyPayment\Api\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyApi\Api\Attribute\ApiResponseData;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\StringQueryOption;
use Wexample\SymfonyApi\Api\Class\ApiResponse;
use Wexample\SymfonyApi\Api\Controller\AbstractApiController;
use Wexample\SymfonyHelpers\Controller\AbstractController;
use Wexample\SymfonyPayment\Api\Dto\PaymentRefundDto;
use Wexample\SymfonyPayment\Api\Normalizer\Entity\PaymentRefund\DefaultPaymentRefundNormalizer;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

/**
 * The refunds of one payment, oldest first: for its payer, or for whoever
 * holds the administration role. Anyone else is told it does not exist.
 */
#[Route(path: 'api/payment-refund/', name: 'api_payment_refund_')]
class PaymentRefundController extends AbstractApiController
{
    final public const string ROUTE_LIST = 'list';

    final public const string QUERY_OPTION_PAYMENT = 'payment';

    #[Route(path: 'list', name: self::ROUTE_LIST, methods: AbstractController::ROUTE_OPTIONS_METHOD_ONLY_GET, options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    #[StringQueryOption(key: self::QUERY_OPTION_PAYMENT, required: true)]
    #[ApiResponseData(PaymentRefundDto::class, collection: true)]
    public function list(
        Request $request,
        PaymentRepository $repository,
        PaymentAccessService $access,
        DefaultPaymentRefundNormalizer $normalizer,
        #[Autowire(param: 'wexample_symfony_payment.administration.role')]
        ?string $administrationRole,
    ): ApiResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $payment = $repository->find((string) self::getQueryOptionValue($request, self::QUERY_OPTION_PAYMENT, ''));
        $administrator = null !== $administrationRole && $this->isGranted($administrationRole);

        if (! $payment || (! $administrator && ! $access->isOwnedBy($payment, $this->getUser()))) {
            throw new NotFoundHttpException();
        }

        $refunds = $payment->getRefunds()->toArray();
        usort($refunds, static fn ($a, $b) => $a->getDateCreated() <=> $b->getDateCreated());

        return self::apiResponseCollection($normalizer->normalizeCollection($refunds));
    }
}
