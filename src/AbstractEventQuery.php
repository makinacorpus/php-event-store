<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore;

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

abstract class AbstractEventQuery implements EventQuery
{
    protected bool $approximateCountPossible = true;
    protected bool $allowApproximateCount = false;
    protected ?UuidInterface $aggregateId = null;
    protected bool $aggregateAsRoot = false;
    protected array $aggregateTypes = [];
    protected ?\DateTimeInterface $dateHigherBound = null;
    protected ?\DateTimeInterface $dateLowerBound = null;
    protected ?bool $failed = false;
    protected int $limit = 0;
    protected array $names = [];
    protected ?string $searchName = null;
    protected $searchData = null;
    protected ?int $position = null;
    protected bool $reverse = false;
    protected ?int $revision = null;
    protected array $properties = [];
    protected array $withoutProperties = [];

    /**
     * Convert value to UUID, raise exception in case of failure
     */
    private function validateUuid($uuid): UuidInterface
    {
        if (\is_string($uuid)) {
            $uuid = Uuid::fromString($uuid);
        }
        if (!$uuid instanceof UuidInterface) {
            throw new \InvalidArgumentException(\sprintf("Aggregate identifier must be a valid UUID string or instanceof of %s: '%s' given", UuidInterface::class, (string)$uuid));
        }
        return $uuid;
    }

    /**
     * Is approximate count allowed.
     *
     * @internal
     *   For implementor usage only.
     */
    public function isApproximateCountAllowed(): bool
    {
        return $this->allowApproximateCount;
    }

    /**
     * We consider that approximate count is possible only where there is no
     * explicit user filters. This matches the following use case:
     *
     *  - When paging, we consider the user will not do LIMIT/OFFSET queries
     *    but WHERE [column] < [some value]. In this use case, having an exact
     *    count or page count is not necessary for browsing.
     *
     *  - As soon as the user asks for filters, the underlaying RDBMS will be
     *    in position that it can use indexes and the query result for counting
     *    so it won't do a seq scan on the whole table.
     *
     * @internal
     *   For implementor usage only.
     */
    public function isApproximateCountPossible(): bool
    {
        return $this->approximateCountPossible;
    }

    /**
     * {@inheritdoc}
     */
    public function allowApproximateCount(bool $toggle = true): EventQuery
    {
        $this->allowApproximateCount = $toggle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function reverse(bool $toggle = false): EventQuery
    {
        $this->reverse = $toggle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fromPosition(int $position): EventQuery
    {
        $this->position = $position;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fromRevision(int $revision): EventQuery
    {
        $this->revision = $revision;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function for($aggregateId, bool $includeRoots = false): EventQuery
    {
        $this->approximateCountPossible = false;

        $this->aggregateId = $this->validateUuid($aggregateId);
        $this->aggregateAsRoot = $includeRoots;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function failed(?bool $toggle = true): EventQuery
    {
        if (null !== $toggle) {
            $this->approximateCountPossible = false;
        }

        $this->failed = $toggle;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withType($typeOrTypes): EventQuery
    {
        \assert(\is_array($typeOrTypes) || \is_string($typeOrTypes));

        $this->approximateCountPossible = false;

        $this->aggregateTypes = \array_unique($this->aggregateTypes += \array_values((array)$typeOrTypes));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withName($nameOrNames): EventQuery
    {
        \assert(\is_array($nameOrNames) || \is_string($nameOrNames));

        $this->approximateCountPossible = false;

        $this->names = \array_unique($this->names += \array_values((array)$nameOrNames));

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withSearchName(string $name): EventQuery
    {
        $this->approximateCountPossible = false;

        $this->searchName = $name;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withSearchData($data): EventQuery
    {
        $this->approximateCountPossible = false;

        $this->searchData = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function withProperty(string $name, ?string $value = null): EventQuery
    {
        $this->approximateCountPossible = false;

        unset($this->withoutProperties[$name]);

        $this->properties[$name][] = $value;

        return $this;
    }

    /**
     * Fetch for which the property is not set or is null.
     */
    public function withoutProperty(string $name): EventQuery
    {
        $this->approximateCountPossible = false;

        if (\array_key_exists($name, $this->properties)) {
            \trigger_error(\sprintf("Query has a value set for this property using withProperty(), call is ignored"), E_USER_WARNING);
        } else {
            $this->withoutProperties[$name] = true;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toDate(\DateTimeInterface $to): EventQuery
    {
        $this->approximateCountPossible = false;

        if ($this->dateHigherBound) {
            \trigger_error(\sprintf("Query has already betweenDates() set, toDate() call is ignored"), E_USER_WARNING);
        } else {
            $this->dateHigherBound = $to;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function fromDate(\DateTimeInterface $from): EventQuery
    {
        $this->approximateCountPossible = false;

        if ($this->dateHigherBound) {
            \trigger_error(\sprintf("Query has already betweenDates() set, fromDate() call is ignored"), E_USER_WARNING);
        } else {
            $this->dateLowerBound = $from;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function betweenDates(\DateTimeInterface $from, \DateTimeInterface $to): EventQuery
    {
        $this->approximateCountPossible = false;

        if ($this->dateLowerBound && !$this->dateHigherBound) {
            \trigger_error(\sprintf("Query has already fromDate() set, betweenDates() call overrides it"), E_USER_WARNING);
        }

        if ($from < $to) {
            $this->dateLowerBound = $from;
            $this->dateHigherBound = $to;
        } else {
            $this->dateLowerBound = $to;
            $this->dateHigherBound = $from;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function limit(int $limit): EventQuery
    {
        if ($limit < 0) {
            throw new \InvalidArgumentException(\sprintf("Limit cannot be less than 0"));
        }

        $this->limit = $limit;

        return $this;
    }
}
