<?php

namespace Wexample\SymfonyPayment\Api\Controller;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Wexample\SymfonyApi\Api\Attribute\ApiResponseData;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\LengthQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\PageQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\SearchQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\SortQueryOption;
use Wexample\SymfonyApi\Api\Attribute\QueryOption\StringQueryOption;
use Wexample\SymfonyApi\Api\Class\ApiResponse;
use Wexample\SymfonyApi\Api\Controller\AbstractApiController;
use Wexample\SymfonyHelpers\Controller\AbstractController;
use Wexample\SymfonyHelpers\Helper\VariableHelper;
use Wexample\SymfonyPayment\Api\Dto\PaymentDto;
use Wexample\SymfonyPayment\Api\Normalizer\Entity\Payment\DefaultPaymentNormalizer;
use Wexample\SymfonyPayment\Repository\PaymentRepository;
use Wexample\SymfonyPayment\Service\PaymentAccessService;

/**
 * The payments a list shows: the reader's own, or — `scope=all`, for whoever
 * holds the administration role — everybody's, narrowed by status (several,
 * comma separated), provider, and words.
 */
#[Route(path: '_api/payment/', name: 'api_payment_')]
class PaymentController extends AbstractApiController
{
    final public const string ROUTE_LIST = 'list';

    final public const string QUERY_OPTION_SCOPE = 'scope';

    final public const string QUERY_OPTION_STATUS = 'status';

    final public const string QUERY_OPTION_PROVIDER = 'provider';

    final public const string SCOPE_MINE = 'mine';

    final public const string SCOPE_ALL = 'all';

    public function __construct(
        private readonly PaymentAccessService $access,
        #[Autowire(param: 'wexample_symfony_payment.administration.role')]
        private readonly ?string $administrationRole,
    ) {
    }

    #[Route(path: 'list', name: self::ROUTE_LIST, methods: AbstractController::ROUTE_OPTIONS_METHOD_ONLY_GET, options: AbstractController::ROUTE_OPTIONS_ONLY_EXPOSE)]
    #[PageQueryOption]
    #[LengthQueryOption]
    #[SearchQueryOption]
    #[StringQueryOption(key: self::QUERY_OPTION_SCOPE, default: self::SCOPE_MINE)]
    #[StringQueryOption(key: self::QUERY_OPTION_STATUS, default: '')]
    #[StringQueryOption(key: self::QUERY_OPTION_PROVIDER, default: '')]
    #[SortQueryOption(allowed: ['dateCreated', 'amount', 'status', 'provider'], default: '-dateCreated')]
    #[ApiResponseData(PaymentDto::class, paginated: true)]
    public function list(
        Request $request,
        PaymentRepository $repository,
        DefaultPaymentNormalizer $normalizer,
    ): ApiResponse {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED');

        $owner = null;

        if (self::SCOPE_ALL === self::getQueryOptionValue($request, self::QUERY_OPTION_SCOPE, self::SCOPE_MINE)) {
            $this->denyUnlessAdministrator();
        } else {
            $owner = $this->access->ownerReferenceOf($this->getUser());
        }

        $statuses = array_values(array_filter(explode(',', (string) self::getQueryOptionValue($request, self::QUERY_OPTION_STATUS, ''))));
        $search = trim((string) self::getQueryOptionValue($request, VariableHelper::SEARCH, ''));

        return self::apiResponseQueryPage(
            $request,
            $repository->queryForList(
                ownerReference: $owner,
                statuses: $statuses,
                provider: (string) self::getQueryOptionValue($request, self::QUERY_OPTION_PROVIDER, '') ?: null,
                search: '' !== $search ? $search : null,
            ),
            static fn (array $payments) => $normalizer->normalizeCollection($payments),
        );
    }

    /**
     * No administration role set, or not held: the whole list does not exist.
     */
    private function denyUnlessAdministrator(): void
    {
        if (null === $this->administrationRole || ! $this->isGranted($this->administrationRole)) {
            throw new NotFoundHttpException();
        }
    }
}
