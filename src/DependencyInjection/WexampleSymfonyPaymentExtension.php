<?php

namespace Wexample\SymfonyPayment\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Wexample\SymfonyHelpers\DependencyInjection\AbstractWexampleSymfonyExtension;
use Wexample\SymfonyPayment\Interface\PayableResolverInterface;

class WexampleSymfonyPaymentExtension extends AbstractWexampleSymfonyExtension
{
    public const string TAG_PAYABLE_RESOLVER = 'wexample_symfony_payment.payable_resolver';

    public function load(
        array $configs,
        ContainerBuilder $container
    ): void {
        $container
            ->registerForAutoconfiguration(PayableResolverInterface::class)
            ->addTag(self::TAG_PAYABLE_RESOLVER);

        $this->loadConfig(
            __DIR__,
            $container
        );
    }
}
