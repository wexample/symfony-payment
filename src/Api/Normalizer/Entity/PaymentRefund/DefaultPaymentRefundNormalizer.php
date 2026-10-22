<?php

namespace Wexample\SymfonyPayment\Api\Normalizer\Entity\PaymentRefund;

use ArrayObject;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Interface\NormalizableDataInterface;
use Wexample\SymfonyHelpers\Normalizer\AbstractEntityNormalizer;
use Wexample\SymfonyPayment\Api\Dto\PaymentRefundDto;
use Wexample\SymfonyPayment\Entity\PaymentRefund;
use Wexample\SymfonyPayment\Entity\Traits\Manipulator\PaymentRefundManipulatorTrait;

class DefaultPaymentRefundNormalizer extends AbstractEntityNormalizer
{
    use PaymentRefundManipulatorTrait;

    public function normalizeEntity(
        PaymentRefund|AbstractEntity $entity,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|NormalizableDataInterface|null {
        return PaymentRefundDto::fromEntity($entity);
    }
}
