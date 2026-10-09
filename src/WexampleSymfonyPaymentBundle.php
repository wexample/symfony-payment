<?php

namespace Wexample\SymfonyPayment;

use Wexample\SymfonyHelpers\Class\AbstractBundle;
use Wexample\SymfonyHelpers\Helper\BundleHelper;
use Wexample\SymfonyHelpers\Interface\LoaderBundleInterface;
use Wexample\SymfonyPseudocode\Interface\PseudocodeBundleInterface;

/**
 * Its entities — a payment and its refunds — are exported to TypeScript from
 * the application installing it, and land in its assets: the generated
 * classes and repositories every app registers on its API client.
 */
class WexampleSymfonyPaymentBundle extends AbstractBundle implements LoaderBundleInterface, PseudocodeBundleInterface
{
    public static function getLoaderFrontPaths(): array
    {
        return [
            BundleHelper::getBundleCssAlias(static::class) => __DIR__.'/../assets/',
        ];
    }

    public static function getPseudocodeSourcePaths(): array
    {
        return [__DIR__.'/'];
    }
}
