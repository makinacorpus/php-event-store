<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\State;

use MakinaCorpus\EventStore\Projector\Error\ProjectorError;

class ProjectorLockedError extends \InvalidArgumentException implements ProjectorError
{
    public function __construct(string $id)
    {
        parent::__construct(\sprintf("Projector '%s' is already locked.", $id));
    }
}
