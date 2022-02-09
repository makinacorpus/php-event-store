<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector;

/**
 * Projector than can be reset and replayed
 */
interface ReplayableProjector extends Projector
{
    /**
     * Delete everything and prepare to replay.
     */
    public function reset(): void;
}
