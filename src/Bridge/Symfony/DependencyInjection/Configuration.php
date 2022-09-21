<?php

declare(strict_types=1);

namespace MakinaCorpus\Files\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

/**
 * @codeCoverageIgnore
 * @todo This should be tested.
 */
final class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('files');
        $rootNode = $treeBuilder->getRootNode();

        // Site-wide specific custom fields.
        $rootNode
            ->children()
                ->arrayNode('index')
                    ->info('File index configuration')
                    ->normalizeKeys(true)
                    ->children()
                        ->scalarNode('driver')
                            ->info('Which driver/backend to use, if null, then no file index will be registered, for now, only "goat" is implemented.')
                            ->defaultNull()
                        ->end()
                        ->variableNode('driver_options')
                            ->info('Driver options.')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
