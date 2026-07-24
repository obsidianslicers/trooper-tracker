<?php

declare(strict_types=1);

namespace Tests\Unit\Hyperdrive;

use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Hyperdrive\MessageDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
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