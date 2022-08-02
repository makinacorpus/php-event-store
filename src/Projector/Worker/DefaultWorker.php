<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\Worker;

use MakinaCorpus\EventStore\Event;
use MakinaCorpus\EventStore\EventStore;
use MakinaCorpus\EventStore\Projector\Projector;
use MakinaCorpus\EventStore\Projector\ProjectorRegistry;
use MakinaCorpus\EventStore\Projector\ReplayableProjector;
use MakinaCorpus\EventStore\Projector\Error\ProjectorNotReplyableError;
use MakinaCorpus\EventStore\Projector\State\ProjectorLockedError;
use MakinaCorpus\EventStore\Projector\State\State;
use MakinaCorpus\EventStore\Projector\State\StateStore;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Default (and propably only) implementation.
 *
 * Interface exists for the need of decorating the worker.
 */
final class DefaultWorker implements Worker, LoggerAwareInterface
{
    use LoggerAwareTrait;

    private ProjectorRegistry $projectorRegistry;
    private EventStore $eventStore;
    private StateStore $stateStore;
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(
        ProjectorRegistry $projectorRegistry,
        EventStore $eventStore,
        StateStore $stateStore
    ) {
        $this->projectorRegistry = $projectorRegistry;
        $this->eventStore = $eventStore;
        $this->stateStore = $stateStore;
        $this->logger = new NullLogger();
    }

    /**
     * {@inheritdoc}
     */
    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher ?? ($this->eventDispatcher = new EventDispatcher());
    }

    /**
     * {@inheritdoc}
     */
    public function play(string $id, WorkerContext $context): void
    {
        $this->doPlay([$this->getProjector($id)], null, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function playFrom(string $id, \DateTimeInterface $from, WorkerContext $context): void
    {
        $this->doPlay([$this->getProjector($id)], $from, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function playAll(WorkerContext $context): void
    {
        $this->doPlay($this->getAllProjectors(), null, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function playAllFrom(\DateTimeInterface $from, WorkerContext $context): void
    {
        $this->doPlay($this->getAllProjectors(), $from, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function reset(string $id): void
    {
        $projector = $this->projectorRegistry->find($id);

        if ($projector instanceof ReplayableProjector) {
            $projector->reset();
        } else {
            $this->logger->error("[DefaultWorker] Player '{player}' is not resettable.", ['player' => $id]);

            throw new ProjectorNotReplyableError($id);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function resetAll(): void
    {
        foreach ($this->getAllProjectors() as $projector) {
            if ($projector instanceof ReplayableProjector) {
                $projector->reset();
            } else {
                $this->logger->notice("[DefaultWorker] Player '{player}' is not resettable, ignoring.", ['player' => $projector->getIdentifier()]);
            }
        }
    }

    /**
     * @param Projector[] $projectors
     */
    private function doPlay(iterable $projectors, ?\DateTimeInterface $from, WorkerContext $context): void
    {
        $states = $this->mapProjectors($projectors, $context);

        if ($date = $this->findLowestDateFromProjectorList($states, $from)) {
            $this->logger->notice("[DefaultWorker] Lowest event date is {date}.", ['date' => $date->format(\DateTime::ISO8601)]);

            $stream = $this->eventStore->query()->fromDate($date)->execute();
        } else {
            $this->logger->notice("[DefaultWorker] No lowest event date found, replying everthing.");

            $stream = $this->eventStore->query()->execute();
        }

        $streamSize = $stream->count();
        $currentIndex = 0;

        $this->dispatch(WorkerEvent::begin($streamSize));

        if ($streamSize <= 0) {
            $this->logger->notice("[DefaultWorker] Stream is empty.");

            $this->dispatch(WorkerEvent::end($streamSize));

            return;
        }

        foreach ($stream as $event) {
            \assert($event instanceof Event);

            $this->dispatch(WorkerEvent::next($streamSize, ++$currentIndex));

            $atLeastOne = false;

            foreach ($states as $id => $projector) {
                $this->logger->notice("[DefaultWorker] Playing events for player '{player}'.", ['player' => $id]);

                \assert($projector instanceof ProjectorState);

                try {
                    if ($projector->stopped) {
                        continue;
                    }

                    if ($projector->position < $event->getPosition()) {
                        $projector->instance->onEvent($event);
                        $projector->lastEvent = $event;
                    }

                    $atLeastOne = true;

                } catch (\Throwable $e) {
                    $this->logger->error("[DefaultWorker] While playing events for player '{player}', error: '{message}'.", ['player' => $id, 'message' => $e->getMessage(), 'exception' => $e]);

                    $projector->stopped = true;

                    $state = $this->stateStore->exception($id, $event, $e, true);
                    $this->dispatch(WorkerEvent::error($streamSize, $currentIndex, $state));
                }
            }

            if (!$atLeastOne) {
                $this->dispatch(WorkerEvent::broken($streamSize, $currentIndex));

                break;
            }
        }

        // Finally update all projectors states.
        foreach ($states as $id => $projector) {
            \assert($projector instanceof ProjectorState);

            if ($projector->lastEvent) {
                $this->logger->error("[DefaultWorker] Setting new state for player '{player}'.", ['player' => $id]);

                $this->stateStore->update($id, $projector->lastEvent, true);
            } else {
                $this->stateStore->unlock($id);
            }
        }

        $this->dispatch(WorkerEvent::end($streamSize, $currentIndex));
    }

    /**
     * @param array<string, ProjectorState> $projectors
     *
     * @return null|\DateTimeInterface
     *   Returning null means projectors with no date exist.
     */
    private function findLowestDateFromProjectorList(array $projectors, ?\DateTimeInterface $minDate): ?\DateTimeInterface
    {
        foreach ($projectors as $projector) {
            \assert($projector instanceof ProjectorState);

            if ($projector->position < 1) {
                return null;
            }

            $projectorDate = $projector->state->getLatestEventDate();

            if (!$minDate) {
                $minDate = $projectorDate;
            } else if ($projectorDate < $minDate) {
                $minDate = $projectorDate;
            }
        }

        return $minDate;
    }

    /**
     * Dispatch event if listeners are attached.
     */
    private function dispatch(WorkerEvent $event): void
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch($event, $event->getEventName());
        }
    }

    /**
     * @param Projector[] $projectors
     *
     * @return array<string, ProjectorState>
     */
    private function mapProjectors(iterable $projectors, WorkerContext $context): array
    {
        $ret = [];

        foreach ($projectors as $projector) {
            \assert($projector instanceof Projector);

            $id = $projector->getIdentifier();

            try {
                $state = $this->stateStore->lock($id);

                if ($context->continueOnError() || !$state->isError()) {
                    $ret[$id] = new ProjectorState($projector, $state);
                } else {
                    $this->logger->error("[DefaultWorker] Player '{player}' is in an erroneous state, skipping.", ['player' => $id]);

                    $this->stateStore->unlock($id);
                }
            } catch (ProjectorLockedError $e) {
                // Do nothing here.
                if ($context->unlock()) {
                    $this->logger->error("[DefaultWorker] Player '{player}' is locked, unlocking.", ['player' => $id]);

                    $state = $this->stateStore->unlock($id);
                    $ret[$id] = new ProjectorState($projector, $state);
                } else {
                    $this->logger->error("[DefaultWorker] Player '{player}' is locked, skipping.", ['player' => $id]);
                }
            }
        }

        if (empty($ret)) {
            throw new MissingProjectorError("All projectors are in error or locked, cannot continue."); 
        }

        return $ret;
    }

    /**
     * @return Projector[]
     */
    private function getAllProjectors(): iterable
    {
        $ret = $this->projectorRegistry->getAll();

        if (empty($ret)) {
            throw new MissingProjectorError("There is no projectors, cannot continue.");
        }

        return $ret;
    }

    private function getProjector(string $id): Projector
    {
        return $this->projectorRegistry->find($id);
    }
}

/**
 * @internal
 */
final class ProjectorState
{
    public Projector $instance;
    public State $state;
    public int $position;
    public ?Event $lastEvent = null;
    public bool $stopped = false;

    public function __construct(Projector $projector, ?State $state)
    {
        $this->instance = $projector;
        $this->state = $state ?? State::empty($projector->getIdentifier());
        $this->position = $this->state->getLatestEventPosition();
    }
}
