<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Testing;

use MakinaCorpus\EventStore\Event;
use MakinaCorpus\EventStore\EventStream;

/**
 * @var \MakinaCorpus\EventStore\Event[]
 */
class DummyArrayEventStream implements EventStream, \Iterator
{
    /** @var Event[] */
    private array $events;
    private int $index = -1;
    private ?Event $current = null;

    /** @var Event */
    public function __construct(array $events)
    {
        $this->events = \array_values($events);

        $this->next();
    }

    /**
     * Fetch next in stream.
     *
     * Warning: iterating over this instance will advance in stream.
     */
    public function fetch(): ?Event
    {
        $this->next();

        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return \count($this->events);
    }

    /**
     * {@inheritdoc}
     */ 
    public function next(): void
    {
        $this->current = $this->events[++$this->index] ?? null;
    }

    /**
     * {@inheritdoc}
     */
    public function valid(): bool
    {
        return null !== $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): mixed
    {
        return $this->current;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void
    {
        $this->index = -1;
        $this->current = null;

        $this->next();
    }

    /**
     * {@inheritdoc}
     */
    public function key(): mixed
    {
        return 0 <= $this->index ? $this->index : null;
    }
}
