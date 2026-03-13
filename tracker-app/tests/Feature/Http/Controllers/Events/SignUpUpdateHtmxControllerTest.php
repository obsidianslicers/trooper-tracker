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
use Tests\TestCase;

class SignUpUpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_event_trooper_status_for_owner(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($event_shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]), [
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);

        $response->assertOk();
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();
        $event_trooper = EventTrooper::factory()->forEventShift($event_shift)->forTrooper($trooper)->create();

        $response = $this->post(route('events.signup-update-htmx', ['event_trooper' => $event_trooper->id]), [
            'status' => EventTrooperStatus::CANCELLED->value,
        ]);

        $response->assertRedirect(route('auth.login'));
    }
}
