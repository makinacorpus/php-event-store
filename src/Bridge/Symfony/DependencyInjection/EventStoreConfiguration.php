<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;

final class EventStoreConfiguration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('event_store');
        $rootNode = $treeBuilder->getRootNode();

        /*
        $rootNode
            ->children()
            ->end()
        ;
         */

        return $treeBuilder;
    }
}
