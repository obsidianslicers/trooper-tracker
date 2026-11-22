<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events;

use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddTrooperFormHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_offers_the_friends_costumes_with_their_only_one_preselected(): void
    {
        [$trooper, $friend, $event_shift, $costume] = $this->friendWithCostumeAndShift();

        $response = $this->actingAs($trooper)->get(
            route('events.add-trooper-form', ['event_shift' => $event_shift->id, 'trooper_id' => $friend->id])
        );

        $response->assertOk();
        $response->assertSee(__('events.Sign up :name', ['name' => $friend->display_name]));
        $response->assertSee("id=\"friend-costume-picker-{$event_shift->id}\"", false);
        $this->assertMatchesRegularExpression("/<option value=\"{$costume->id}\"\\s+selected>Friend Armor<\\/option>/", $response->getContent());
        $response->assertSee(__('events.Attending without a costume'));
        $response->assertDontSee("id=\"friend-station-picker-{$event_shift->id}\"", false);
        $response->assertDontSee("id=\"add-org-confirm-{$event_shift->id}\"", false);
    }

    public function test_invoke_asks_for_a_station_when_the_shift_has_stations(): void
    {
        [$trooper, $friend, $event_shift] = $this->friendWithCostumeAndShift();
        $station = EventShiftStation::factory()->forEventShift($event_shift)->create();

        $response = $this->actingAs($trooper)->get(
            route('events.add-trooper-form', ['event_shift' => $event_shift->id, 'trooper_id' => $friend->id])
        );

        $response->assertOk();
        $response->assertSee("id=\"friend-station-picker-{$event_shift->id}\"", false);
        $response->assertSee($station->name);
    }

    public function test_invoke_says_which_organization_when_the_event_limits_it(): void
    {
        [$trooper, $friend, $event_shift] = $this->friendWithCostumeAndShift(organization_limit: 5);

        $response = $this->actingAs($trooper)->get(
            route('events.add-trooper-form', ['event_shift' => $event_shift->id, 'trooper_id' => $friend->id])
        );

        $response->assertOk();
        $response->assertSee("id=\"add-org-confirm-{$event_shift->id}\"", false);
        $response->assertSee(__('events.Trooping as :organization', ['organization' => 'Friend Garrison']));
    }

    public function test_invoke_requires_authentication(): void
    {
        [, $friend, $event_shift] = $this->friendWithCostumeAndShift();

        $this->get(route('events.add-trooper-form', ['event_shift' => $event_shift->id, 'trooper_id' => $friend->id]))
            ->assertRedirect(route('auth.login'));
    }

    /**
     * @return array{Trooper, Trooper, EventShift, Costume}
     */
    private function friendWithCostumeAndShift(?int $organization_limit = null): array
    {
        $organization = Organization::factory()->create(['name' => 'Friend Garrison']);
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        $friend = Trooper::factory()->asActive()->withVerifiedEmail()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->asMember()->create();
        TrooperAssignment::factory()->forTrooper($friend)->forOrganization($organization)->asMember()->create();

        $event = Event::factory()->withOrganization($organization)->create([
            Event::TROOPERS_ALLOWED => null,
            Event::HANDLERS_ALLOWED => null,
        ]);
        EventOrganization::factory()
            ->state([
                EventOrganization::EVENT_ID => $event->id,
                EventOrganization::ORGANIZATION_ID => $organization->id,
                EventOrganization::CAN_ATTEND => true,
                EventOrganization::TROOPERS_ALLOWED => $organization_limit,
            ])
            ->create();
        $event_shift = EventShift::factory()->forEvent($event)->create();

        $costume = Costume::factory()->state(['name' => 'Friend Armor'])->create();
        $organization_costume = OrganizationCostume::factory()
            ->state([OrganizationCostume::ORGANIZATION_ID => $organization->id, OrganizationCostume::COSTUME_ID => $costume->id])
            ->create();
        TrooperCostume::factory()
            ->state([TrooperCostume::TROOPER_ID => $friend->id, TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id])
            ->create();

        return [$trooper, $friend, $event_shift, $costume];
    }
}
