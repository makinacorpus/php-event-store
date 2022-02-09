<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore;

use Ramsey\Uuid\UuidInterface;

/**
 * Event builder.
 */
interface EventBuilder
{
    /**
     * Set message name.
     *
     * @return $this
     */
    public function name(string $name): self;

    /**
     * Set validity date if not now.
     *
     * Warning: this will not modify the event position, only its date.
     *
     * @return $this
     */
    public function date(\DateTimeInterface $date): self;

    /**
     * With aggregate information.
     *
     * If no UUID is provided, new one will be generated.
     *
     * @return $this
     */
    public function aggregate(?string $type, ?UuidInterface $id = null): self;

    /**
     * With given property value.
     *
     * @param ?string $value
     *   If set to null, explicitely remove property.
     *
     * @return $this
     */
    public function property(string $name, ?string $value): self;

    /**
     * With given mulitple property value.
     *
     * @param array<string,null|string> $properties
     *   If set to null, explicitely remove property.
     *
     * @return $this
     */
    public function properties(array $properties): self;

    /**
     * Execute operation.
     */
    public function execute(): Event;
}
