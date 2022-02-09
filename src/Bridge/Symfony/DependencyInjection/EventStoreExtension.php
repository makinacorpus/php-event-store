<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Bridge\Symfony\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class EventStoreExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        /* $config = */ $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(\dirname(__DIR__).'/Resources/config'));

        $consoleEnabled = \class_exists(Command::class);

        $loader->load('event-store.yaml');
        $loader->load('event-projector.yaml');
        if ($consoleEnabled) {
            $loader->load('event-store-console.yaml');
            $loader->load('event-projector-console.yaml');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new EventStoreConfiguration();
    }
}
