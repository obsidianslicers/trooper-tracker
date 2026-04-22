<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MissionBriefAcknowledgeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_trooper_can_acknowledge_mission_brief(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $event = Event::factory()->create([
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
        ]);

        $response = $this->actingAs($trooper)->post(route('events.ack-mission-brief', ['event' => $event->id]));

        $response->assertRedirect(route('events.display', ['event' => $event->id]));

        $this->assertDatabaseHas('tt_event_mission_acks', [
            'event_id' => $event->id,
            'trooper_id' => $trooper->id,
        ]);
    }

    public function test_guest_is_redirected_to_login_when_acknowledging(): void
    {
        $event = Event::factory()->create([
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
        ]);

        $response = $this->post(route('events.ack-mission-brief', ['event' => $event->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
