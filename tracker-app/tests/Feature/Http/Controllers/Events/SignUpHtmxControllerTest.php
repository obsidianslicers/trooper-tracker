<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SignUpHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_signs_up_trooper_and_returns_shift_partial(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->actingAs($trooper)->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertOk();
    }

    public function test_invoke_saves_organization_id_from_request(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()
            ->withOrganization($organization)
            ->state([Event::TROOPERS_ALLOWED => null])
            ->create();
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $this->actingAs($trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['organization_id' => $organization->id]
        );

        $this->assertDatabaseHas('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $trooper->id,
            EventTrooper::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();
        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $response = $this->post(route('events.signup-htmx', ['event_shift' => $event_shift->id]));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_does_not_sign_up_friend_when_auth_trooper_not_going(): void
    {
        $auth_trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()->forTrooper($auth_trooper)->forOrganization($organization)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($friend)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $this->actingAs($auth_trooper)->post(
            route('events.signup-htmx', ['event_shift' => $event_shift->id]),
            ['trooper_id' => $friend->id]
        )->assertOk();

        $this->assertDatabaseMissing('tt_event_troopers', [
            EventTrooper::TROOPER_ID => $friend->id,
            EventTrooper::EVENT_SHIFT_ID => $event_shift->id,
        ]);
    }
}
