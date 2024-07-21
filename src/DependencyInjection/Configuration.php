<?php

/*
 * This file is part of the jonasarts Notification bundle package.
 *
 * (c) Jonas Hauser <symfony@jonasarts.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace jonasarts\Bundle\NotificationBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('notification');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('template')
                    ->addDefaultsIfNotSet()
                    ->children()

                        ->scalarNode('loader')
                            ->defaultValue('clone')
                            ->end()
                        ->scalarNode('path')
                            ->defaultNull()
                            ->end()
                    ->end()
                ->end()

                ->arrayNode('from')
                    ->addDefaultsIfNotSet()
                    ->children()

                        // default from address
                        ->scalarNode('address')
                            ->isRequired()
                            ->cannotBeEmpty()
                            ->defaultValue('nobody@domain.tld')
                        ->end()
                        // default from name
                        ->scalarNode('name')
                            ->defaultValue('Mr. Nobody')
                        ->end()

                    ->end()
                ->end()

                ->arrayNode('sender')
                    ->addDefaultsIfNotSet()
                    ->children()

                        // default sender address
                        ->scalarNode('address')
                            ->defaultNull()
                        ->end()
                        // default sender name
                        ->scalarNode('name')
                            ->defaultNull()
                        ->end()

                    ->end()
                ->end()

                ->arrayNode('reply_to')
                    ->addDefaultsIfNotSet()
                    ->children()

                        // default sender address
                        ->scalarNode('address')
                            ->defaultNull()
                        ->end()
                        // default sender name
                        ->scalarNode('name')
                            ->defaultNull()
                        ->end()

                    ->end()
                ->end()

                ->scalarNode('return_path')
                    ->defaultNull()
                ->end()

                // optional subject prefix
                ->scalarNode('subject_prefix')
                    ->defaultNull()
                ->end()

            ->end();

        return $treeBuilder;
    }
}
