<?php

declare(strict_types=1);

namespace Tests\Unit\Hyperdrive;

use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Concerns\ShouldBeTransactional;
use Hyperdrive\Message;
use Hyperdrive\MessageDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class MessageDispatcherActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_injects_the_authenticated_trooper_into_an_actor_parameter(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $this->actingAs($trooper);

        $subject = app(MessageDispatcher::class);
        $result = $subject->handle(TestActorMessage::class);

        $this->assertInstanceOf(Trooper::class, $result);
        $this->assertSame($trooper->id, $result->getKey());
    }

    public function test_transactional_messages_join_an_existing_transaction(): void
    {
        $subject = app(MessageDispatcher::class);

        $transaction_level = $subject->handle(TestTransactionalMessage::class);

        $this->assertSame(1, $transaction_level);
        $this->assertSame(1, DB::transactionLevel());
    }
}

final class TestActorMessage extends Message
{
    public function __construct(public Actor $actor)
    {
    }

    public function handle(): Actor
    {
        return $this->actor;
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
