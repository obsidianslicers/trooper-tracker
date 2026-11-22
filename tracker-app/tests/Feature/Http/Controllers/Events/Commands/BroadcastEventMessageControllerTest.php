<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Commands;

use App\Jobs\BroadcastEventMessageJob;
use App\Models\Event;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class BroadcastEventMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $moderator;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->create();
        $this->moderator = Trooper::factory()->asModerator()->withVerifiedEmail()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $this->moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $this->event = Event::factory()->create([
            Event::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_queues_broadcast_and_says_it_was_sent(): void
    {
        Queue::fake();
        $message = 'Please review the updated event details.';

        $response = $this->actingAs($this->moderator)
            ->post(route('events.broadcast-message', ['event' => $this->event->id]), [
                'message' => $message,
            ]);

        $response->assertRedirect(route('events.display', ['event' => $this->event]));
        $response->assertSessionHas('toast.success', 'Message sent.');
        Queue::assertPushed(BroadcastEventMessageJob::class, function (BroadcastEventMessageJob $job) use ($message): bool
        {
            return $job->event->id === $this->event->id
                && $job->message === $message;
        });
    }

    public function test_invoke_returns_validation_error_without_queueing_broadcast(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->moderator)
            ->post(route('events.broadcast-message', ['event' => $this->event->id]), [
                'message' => '',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['message' => 'The message field is required.']);
        Queue::assertNothingPushed();
    }
}
