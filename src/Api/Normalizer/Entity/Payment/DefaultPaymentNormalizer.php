<?php

namespace Wexample\SymfonyPayment\Api\Normalizer\Entity\Payment;

use ArrayObject;
use Wexample\SymfonyHelpers\Entity\AbstractEntity;
use Wexample\SymfonyHelpers\Interface\NormalizableDataInterface;
use Wexample\SymfonyHelpers\Normalizer\AbstractEntityNormalizer;
use Wexample\SymfonyPayment\Api\Dto\PaymentDto;
use Wexample\SymfonyPayment\Entity\Payment;
use Wexample\SymfonyPayment\Entity\Traits\Manipulator\PaymentManipulatorTrait;

class DefaultPaymentNormalizer extends AbstractEntityNormalizer
{
    use PaymentManipulatorTrait;

    public function normalizeEntity(
        Payment|AbstractEntity $entity,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|ArrayObject|NormalizableDataInterface|null {
        return PaymentDto::fromEntity($entity);
    }
}
