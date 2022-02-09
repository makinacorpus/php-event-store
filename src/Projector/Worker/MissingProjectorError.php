<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Projector\Worker;

use MakinaCorpus\EventStore\Projector\Error\ProjectorError;

class MissingProjectorError extends \InvalidArgumentException implements ProjectorError
{
}
