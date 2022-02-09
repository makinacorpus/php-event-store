<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore;

use MakinaCorpus\Message\Property;
use MakinaCorpus\Message\BackwardCompat\AggregateMessage;
use MakinaCorpus\Message\Property\WithPropertiesTrait;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

/**
 * Property names are AMQP compatible, except for 'type', and 'X-*' that should
 * be message properties by the AMQP spec.
 */
final class Event
{
    use WithPropertiesTrait;

    private ?UuidInterface $aggregateId = null;
    private ?UuidInterface $aggregateRoot = null;
    private ?string $aggregateType = null;
    private \DateTimeInterface $createdAt;
    private \DateTimeInterface $validAt;
    private mixed $data;
    private ?int $errorCode = null;
    private ?string $errorMessage = null;
    private ?string $errorTrace = null;
    private bool $hasFailed = false;
    private string $name;
    private ?string $namespace = null;
    private int $position = 0;
    private int $revision = 0;

    public function __construct(
        mixed $data,
        string $name = null,
        array $properties = [],
        ?UuidInterface $aggregateId = null,
        ?UuidInterface $aggregateRoot = null,
        ?string $aggregateType = null,
        ?\DateTimeInterface $createdAt = null,
        ?\DateTimeInterface $validAt = null,
        ?string $namespace = null,
        int $position = 0,
        int $revision = 0,
        bool $hasFailed = false,
        ?int $errorCode = null,
        ?string $errorMessage = null,
        ?string $errorTrace = null
    ) {
        if ($data instanceof AggregateMessage) {
            @\trigger_error(\sprintf("Using class '%s' is deprecated and discouraged.", AggregateMessage::class), E_USER_DEPRECATED);
            if (!$aggregateId) {
                $aggregateId = $data->getAggregateId();
            }
            if (!$aggregateType) {
                $aggregateType = $data->getAggregateType();
            }
            if (!$aggregateRoot) {
                $aggregateRoot = $data->getAggregateRoot();
            }
        }

        if (!$createdAt) {
            $createdAt = $validAt ?? new \DateTimeImmutable();
        }
        if (!$validAt) {
            $validAt = $createdAt;
        }
        if (!$name) {
            $name = \is_object($data) ? \get_class($data) : \get_debug_type($data);
        }

        $this->setProperties($properties);

        $this->aggregateId = $aggregateId;
        $this->aggregateRoot = $aggregateRoot;
        $this->aggregateType = $aggregateType;
        $this->createdAt = $createdAt;
        $this->data = $data;
        $this->errorCode = $errorCode;
        $this->errorMessage = $errorMessage;
        $this->errorTrace = $errorTrace;
        $this->hasFailed = $hasFailed;
        $this->name = $name;
        $this->namespace = $namespace;
        $this->position = $position;
        $this->revision = $revision;
        $this->validAt = $validAt;
    }

    /**
     * Get position in the whole namespace
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Compute UUID from internal data
     */
    private function computeUuid(): UuidInterface
    {
        return Uuid::uuid4();
    }

    /**
     * Get aggregate identifier
     */
    public function getAggregateId(): UuidInterface
    {
        return $this->aggregateId ?? ($this->aggregateId = $this->computeUuid());
    }

    /**
     * Get aggregate root identifier, if any set
     */
    public function getAggregateRoot(): ?UuidInterface
    {
        return $this->aggregateRoot;
    }

    /**
     * Get revision for the aggregate
     */
    public function getRevision(): int
    {
        return $this->revision;
    }

    /**
     * Get aggregate type
     */
    public function getAggregateType(): string
    {
        return $this->aggregateType ?? Property::DEFAULT_TYPE;
    }

    /**
     * Get event name (the message class name in most case)
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get creation date.
     *
     * You MUST NOT use this for business purpose, use validity date instead.
     *
     * @see Event::validAt()
     */
    public function createdAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * Get validity date.
     *
     * Validity date is the moment in time the event is considered done. This
     * field exists because events can be amended to fix history in case of bugs
     * were spotted.
     *
     * Creation date MUST NOT be used for business purposes, only validation
     * date can be.
     */
    public function validAt(): \DateTimeInterface
    {
        return $this->validAt;
    }

    /**
     * Has the transaction or publication failed (if it has failed, transaction is considered as rollbacked)
     */
    public function hasFailed(): bool
    {
        return $this->hasFailed;
    }

    /**
     * Is this event persisted
     */
    public function isStored(): bool
    {
        return $this->revision !== 0;
    }

    /**
     * In case of failure, get error code
     */
    public function getErrorCode(): ?int
    {
        return $this->errorCode;
    }

    /**
     * In case of failure, get error message
     */
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }

    /**
     * In case of failure, get error trace
     */
    public function getErrorTrace(): ?string
    {
        return $this->errorTrace;
    }

    /**
     * Get real message that was stored along
     */
    public function getMessage(): ?object
    {
        if (\is_callable($this->data)) {
            $this->data = \call_user_func($this->data);
        }

        return $this->data;
    }
}
