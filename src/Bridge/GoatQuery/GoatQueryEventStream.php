<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Bridge\GoatQuery;

use Goat\Runner\ResultIterator;
use MakinaCorpus\EventStore\Event;
use MakinaCorpus\EventStore\EventStream;

final class GoatQueryEventStream implements \IteratorAggregate, EventStream
{
    private ResultIterator $result;
    private GoatQueryEventStore $eventStore;

    public function __construct(ResultIterator $result, GoatQueryEventStore $eventStore)
    {
        $this->result = $result;
        $this->eventStore = $eventStore;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return $this->result->countRows();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->result as $row) {
            yield $this->eventStore->hydrateEvent($row);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function fetch(): ?Event
    {
        if ($row = $this->result->fetch()) {
            return $this->eventStore->hydrateEvent($row);
        }
        return null;
    }
}
