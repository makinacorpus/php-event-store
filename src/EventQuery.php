<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore;

/**
 * Event query builder
 */
interface EventQuery
{
    /**
     * Set reverse order search (from latest to oldest).
     *
     * @return $this
     */
    public function reverse(bool $toggle = false): EventQuery;

    /**
     * Fetch events starting from position.
     *
     * @return $this
     */
    public function fromPosition(int $position): EventQuery;

    /**
     * Fetch events starting from revision.
     *
     * @return $this
     */
    public function fromRevision(int $revision): EventQuery;

    /**
     * Fetch events for aggregate.
     *
     * @param string|\Ramsey\Uuid\UuidInterface $aggregateId
     *
     * @return $this
     */
    public function for($aggregateId, bool $includeRoots = false): EventQuery;

    /**
     * Fetch events that have been failed or not, set null to drop filter
     * default is to always exclude failed events.
     *
     * @return $this
     */
    public function failed(?bool $toggle = true): EventQuery;

    /**
     * Fetch with aggregate type.
     *
     * @return $this
     */
    public function withType($typeOrTypes): EventQuery;

    /**
     * Fetch with the given event names.
     *
     * @param string|string[] $nameOrNames
     *
     * @return $this
     */
    public function withName($nameOrNames): EventQuery;

    /**
     * Fetch the given $name part (insensitive) in the real Name.
     *
     * @return $this
     */
    public function withSearchName(string $name): EventQuery;

    /**
     * Fetch with the given data in the raw message data.
     *
     * @return $this
     */
    public function withSearchData($data): EventQuery;

    /**
     * Fetch with the property, if value is set it will lookup for the given value as well.
     *
     * @return $this
     */
    public function withProperty(string $name, ?string $value = null): EventQuery;

    /**
     * Fetch for which the property is not set or is null.
     *
     * @return $this
     */
    public function withoutProperty(string $name): EventQuery;

    /**
     * Fetch events starting from date, ignored if date bounds are already set using betweenDate().
     *
     * @return $this
     */
    public function fromDate(\DateTimeInterface $from): EventQuery;

    /**
     * Fetch events until date, ignored if date bounds are already set using betweenDate().
     *
     * @return $this
     */
    public function toDate(\DateTimeInterface $to): EventQuery;

    /**
     * Fetch event between provided dates, order does not matter, will override fromDate().
     *
     * @return $this
     */
    public function betweenDates(\DateTimeInterface $from, \DateTimeInterface $to): EventQuery;

    /**
     * Limit the number of returned rows.
     *
     * If given parameter is 0, there is no limit.
     *
     * @return $this
     */
    public function limit(int $limit): EventQuery;

    /**
     * Execute this query and fetch event stream.
     */
    public function execute(): EventStream;
}
