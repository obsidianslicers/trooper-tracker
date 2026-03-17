<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Enums\EventGuestStatus;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuestUpdateHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $trooper;

    private EventShift $event_shift;

    private EventGuest $event_guest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $organization = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($this->trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event = Event::factory()->withOrganization($organization)->create();
        $this->event_shift = EventShift::factory()->forEvent($event)->create();

        $this->event_guest = EventGuest::factory()
            ->forEventShift($this->event_shift)
            ->forTrooper($this->trooper)
            ->create();
    }

    public function test_invoke_updates_status_for_owner(): void
    {
        $response = $this->actingAs($this->trooper)->post(
            route('events.guest-update-htmx', ['event_guest' => $this->event_guest->id]),
            ['status' => EventGuestStatus::CANCELLED->value]
        );

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_guests', [
            EventGuest::ID => $this->event_guest->id,
            EventGuest::STATUS => EventGuestStatus::CANCELLED->value,
        ]);
    }

    public function test_invoke_updates_name_for_owner(): void
    {
        $response = $this->actingAs($this->trooper)->post(
            route('events.guest-update-htmx', ['event_guest' => $this->event_guest->id]),
            ['name' => 'Wedge Antilles']
        );

        $response->assertOk();

        $this->assertDatabaseHas('tt_event_guests', [
            EventGuest::ID => $this->event_guest->id,
            EventGuest::NAME => 'Wedge Antilles',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(
            route('events.guest-update-htmx', ['event_guest' => $this->event_guest->id]),
            ['status' => EventGuestStatus::CANCELLED->value]
        );

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_403_for_non_owner(): void
    {
        $other_trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()
            ->forTrooper($other_trooper)
            ->forOrganization($other_org)
            ->asMember()
            ->create();

        $response = $this->actingAs($other_trooper)->post(
            route('events.guest-update-htmx', ['event_guest' => $this->event_guest->id]),
            ['status' => EventGuestStatus::CANCELLED->value]
        );

        $response->assertForbidden();
    }

    public function test_invoke_ignores_request_with_no_recognised_field(): void
    {
        $response = $this->actingAs($this->trooper)->post(
            route('events.guest-update-htmx', ['event_guest' => $this->event_guest->id]),
            ['unrecognised_field' => 'some_value']
        );

        $response->assertOk();
    }
}
