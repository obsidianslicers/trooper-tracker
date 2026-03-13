<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ShiftCompleteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_not_found_for_invalid_status_token(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->create();

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => 'invalid-token',
        ]));

        $response->assertNotFound();
    }

    public function test_invoke_updates_status_with_valid_token(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->asGoing()->create();

        $status = Crypt::encryptString(EventTrooperStatus::ATTENDED->value);

        $response = $this->actingAs($trooper)->get(route('events.shift-complete', [
            'event_trooper' => $event_trooper->id,
            'status' => $status,
        ]));

        $response->assertOk();
        $response->assertViewIs('pages.events.shift-complete');
    }
}
