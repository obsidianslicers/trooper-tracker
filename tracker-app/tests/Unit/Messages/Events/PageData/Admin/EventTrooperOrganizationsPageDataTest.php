<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\PageData\Admin;

use App\Messages\Events\PageData\Admin\EventTrooperOrganizationsPageData;
use App\Messages\Events\Resources\EventTrooperCreditOptions;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EventTrooperOrganizationsPageDataTest extends TestCase
{
    use RefreshDatabase;

    private Trooper $admin;

    private Trooper $trooper;

    private Organization $organization;

    private Event $event;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Trooper::factory()->asSystemOwner()->create();
        $this->trooper = Trooper::factory()->asActive()->create();
        $this->organization = Organization::factory()->create();
        $this->organization->update([Organization::NODE_PATH => (string) $this->organization->id]);
        $this->event = Event::factory()->withOrganization($this->organization)->create();
    }

    public function test_handle_ticks_every_club_a_newly_picked_costume_can_credit(): void
    {
        $old_costume = Costume::factory()->create();
        $new_costume = Costume::factory()->create();
        EventOrganization::factory()->create([
            EventOrganization::EVENT_ID => $this->event->id,
            EventOrganization::ORGANIZATION_ID => $this->organization->id,
            EventOrganization::CAN_ATTEND => true,
        ]);
        $org_costume = OrganizationCostume::factory()
            ->forOrganization($this->organization)
            ->forCostume($new_costume)
            ->create();
        TrooperCostume::factory()->forTrooper($this->trooper)->forOrganizationCostume($org_costume)->create();
        TrooperOrganization::factory()->forTrooper($this->trooper)->forOrganization($this->organization)->create();
        $event_trooper = $this->signUp([EventTrooper::COSTUME_ID => $old_costume->id]);

        $subject = new EventTrooperOrganizationsPageData($this->admin, $event_trooper, $new_costume->id);
        $result = $subject->handle();

        $this->assertInstanceOf(EventTrooperCreditOptions::class, $result);
        $data = $result->resolve(new Request);
        $this->assertContains($this->organization->id, collect($data['org_options'])->pluck('id')->all());
        $this->assertContains($this->organization->id, $data['organization_ids']);
    }

    public function test_handle_offers_member_clubs_when_there_is_no_costume(): void
    {
        TrooperAssignment::factory()->forTrooper($this->trooper)->forOrganization($this->organization)->asMember()->create();
        $event_trooper = $this->signUp([EventTrooper::COSTUME_ID => null]);

        $subject = new EventTrooperOrganizationsPageData($this->admin, $event_trooper);
        $data = $subject->handle()->resolve(new Request);

        $this->assertContains($this->organization->id, collect($data['org_options'])->pluck('id')->all());
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function signUp(array $attributes): EventTrooper
    {
        $event_shift = EventShift::factory()->forEvent($this->event)->create();

        return EventTrooper::factory()->forEventShift($event_shift)->forTrooper($this->trooper)->create($attributes);
    }
}
