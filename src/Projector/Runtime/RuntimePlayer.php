<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\Runtime;

use MakinaCorpus\EventStore\Event;

interface RuntimePlayer
{
    /**
     * Play a single event over all projectors.
     */
    public function dispatch(Event $event): void;
}
