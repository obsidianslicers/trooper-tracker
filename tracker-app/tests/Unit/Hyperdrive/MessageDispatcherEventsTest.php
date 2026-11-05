<?php

declare(strict_types=1);

namespace Tests\Unit\Hyperdrive;

use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Events\MessageHandled;
use Hyperdrive\Events\MessageHandling;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

final class MessageDispatcherEventsTest extends TestCase
{
    public function test_it_fires_handling_and_handled_events_around_the_message(): void
    {
        Event::fake([MessageHandling::class, MessageHandled::class]);

        $result = TestEchoMessage::call(value: 'hi');

        $this->assertSame('hi!', $result);
        Event::assertDispatched(
            MessageHandling::class,
            fn (MessageHandling $e): bool => $e->message instanceof TestEchoMessage,
        );
        Event::assertDispatched(
            MessageHandled::class,
            fn (MessageHandled $e): bool => $e->result === 'hi!',
        );
    }

    public function test_handled_fires_after_a_transactional_message_commits(): void
    {
        $levels = [];
        Event::listen(MessageHandled::class, function () use (&$levels): void
        {
            $levels[] = DB::transactionLevel();
        });

        TestTransactionalMessage::call();

        $this->assertSame([0], $levels);
    }

    public function test_handled_does_not_fire_when_the_message_throws(): void
    {
        Event::fake([MessageHandled::class]);

        try
        {
            TestFailingMessage::call();
        }
        catch (RuntimeException)
        {
        }

        Event::assertNotDispatched(MessageHandled::class);
    }
}

final class TestEchoMessage extends Message
{
    public function __construct(private readonly string $value) {}

    public function handle(): string
    {
        return $this->value.'!';
    }
}

final class TestTransactionalMessage extends Message
{
    use ShouldBeTransactional;

    public function handle(): int
    {
        return DB::transactionLevel();
    }
}

final class TestFailingMessage extends Message
{
    public function handle(): void
    {
        throw new RuntimeException('nope');
    }
}
