<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore;

/**
 * @var \MakinaCorpus\EventStore\Event[]
 */
interface EventStream extends \Traversable, \Countable
{
    /**
     * Fetch next in stream.
     *
     * Warning: iterating over this instance will advance in stream.
     */
    public function fetch(): ?Event;
}
