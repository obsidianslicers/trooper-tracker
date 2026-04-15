<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MissionBriefRequirementTest extends TestCase
{
    use RefreshDatabase;

    public function test_signup_blocked_when_mission_brief_ack_required_and_missing(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create([
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
        ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_signup_allowed_when_mission_brief_ack_required_and_present(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create([
            Event::REQUIRE_MISSION_BRIEF_ACK => true,
        ]);
        $event_shift = EventShift::factory()->forEvent($event)->create();

        DB::table('tt_event_mission_acks')->insert([
            'event_id' => $event->id,
            'trooper_id' => $trooper->id,
            'acknowledged_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
            EventTrooper::TROOPER_ID => $trooper->id,
        ]);
    }
}
