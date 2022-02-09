<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Bridge\Symfony;

use MakinaCorpus\EventStore\Bridge\Symfony\DependencyInjection\EventStoreExtension;
use MakinaCorpus\EventStore\Bridge\Symfony\DependencyInjection\Compiler\DomainConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * @codeCoverageIgnore
 */
final class EventStoreBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new DomainConfigurationPass());
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        return new EventStoreExtension();
    }
}
