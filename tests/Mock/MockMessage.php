<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Tests\Mock;

use MakinaCorpus\Message\BackwardCompat\AggregateMessage;
use MakinaCorpus\Message\BackwardCompat\AggregateMessageTrait;

class MockMessage implements AggregateMessage
{
    use AggregateMessageTrait;
}
