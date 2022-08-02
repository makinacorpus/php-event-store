<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\Worker;

class WorkerContext
{
    private bool $continueOnError = false;
    private bool $reset = false;
    private bool $unlock = false;

    public function __construct(
        bool $continueOnError = false,
        bool $reset = false,
        bool $unlock = false
    ) {
        $this->continueOnError = $continueOnError;
        $this->reset = $reset;
        $this->unlock = $unlock;
    }

    public function continueOnError(): bool
    {
        return $this->continueOnError;
    }

    public function reset(): bool
    {
        return $this->reset;
    }

    public function unlock(): bool
    {
        return $this->unlock;
    }
}
