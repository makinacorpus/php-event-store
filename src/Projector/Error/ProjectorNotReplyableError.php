<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\Error;

class ProjectorNotReplyableError extends \InvalidArgumentException implements ProjectorError
{
    public function __construct(string $id)
    {
        parent::__construct(\sprintf("Projector '%s' cannot be reset", $id));
    }
}
