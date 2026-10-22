<?php

namespace Wexample\SymfonyPayment\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_payment');

        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('administration')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('role')
                            ->info('The role reading every payment and acting on them — refunding, confirming a manual payment, synchronizing —, through the API and the pages of symfony-payment-ds. Unset — the default —, nobody has it: what it opens answers 404.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
