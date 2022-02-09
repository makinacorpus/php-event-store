<?php

declare(strict_types=1);

namespace MakinaCorpus\EventStore\Tests;

use MakinaCorpus\EventStore\DefaultEventBuilder;
use MakinaCorpus\EventStore\Event;
use MakinaCorpus\EventStore\Tests\Mock\MockMessage;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;

final class DefaultEventBuilderTest extends TestCase
{
    public function testMessage(): void
    {
        $builder = new DefaultEventBuilder(fn () => new Event(new MockMessage()));

        $message = new MockMessage();

        $builder->message($message, 'foo.bar');
        $builder->name('foo.bar');

        self::assertSame($message, $builder->getMessage());
        self::assertSame('foo.bar', $builder->getMessageName());
    }

    public function testAggregate(): void
    {
        $builder = new DefaultEventBuilder(fn () => new Event(new MockMessage()));

        $id = Uuid::uuid4();

        $builder->aggregate('bar.baz', $id);

        self::assertSame('bar.baz', $builder->getAggregateType());
        self::assertTrue($id->equals($builder->getAggregateId()));

        $builder->aggregate('pouf', null);

        self::assertSame('pouf', $builder->getAggregateType());
        self::assertNull($builder->getAggregateId());
    }

    public function testProperty(): void
    {
        $builder = new DefaultEventBuilder(fn () => new Event(new MockMessage()));

        $builder->property('foo', 'This is foo.');
        $builder->property('bar', 'This is bar.');

        self::assertSame(
            [
                'foo' => 'This is foo.',
                'bar' => 'This is bar.',
            ],
            $builder->getProperties()
        );
    }

    public function testProperties(): void
    {
        $builder = new DefaultEventBuilder(fn () => new Event(new MockMessage()));

        $builder->properties([
            'foo' => 'This is foo.',
            'bar' => 'This is bar.',
        ]);

        self::assertSame(
            [
                'foo' => 'This is foo.',
                'bar' => 'This is bar.',
            ],
            $builder->getProperties()
        );
    }

    public function testSetWhenLockedRaiseError(): void
    {
        $builder = new DefaultEventBuilder(fn () => new Event(new MockMessage()));

        $builder->execute();

        self::expectException(\BadMethodCallException::class);
        $builder->aggregate('foo');
    }

    public function testGetMessageWhenNotSetFails(): void
    {
        $builder = new DefaultEventBuilder(fn () => new Event(new MockMessage()));

        self::expectException(\BadMethodCallException::class);
        $builder->getMessage();
    }

    public function testExecuteCallsConstructorCallable(): void
    {
        $event = new Event(new MockMessage());
        $builder = new DefaultEventBuilder(fn (DefaultEventBuilder $builder) => $event);

        $result = $builder->execute();

        self::assertSame($event, $result);
    }
}
