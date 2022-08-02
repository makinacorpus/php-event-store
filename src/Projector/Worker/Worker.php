<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\Worker;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Projector player.
 */
interface Worker
{
    /**
     * Get internal event dispatcher.
     */
    public function getEventDispatcher(): EventDispatcherInterface;

    /**
     * Play event stream for a single projector.
     */
    public function play(string $id, WorkerContext $context): void;

    /**
     * Play event stream for a single projector from date.
     */
    public function playFrom(string $id, \DateTimeInterface $date, WorkerContext $context): void;

    /**
     * Play event stream for all projectors.
     */
    public function playAll(WorkerContext $context): void;

    /**
     * Play event stream for all projectors from date.
     */
    public function playAllFrom(\DateTimeInterface $date, WorkerContext $context): void;

    /**
     * Reset all data of a single projector.
     */
    public function reset(string $id): void;

    /**
     * Rest all data for all projectors.
     *
     * Warning: you should probably never call this.
     */
    public function resetAll(): void;
}
