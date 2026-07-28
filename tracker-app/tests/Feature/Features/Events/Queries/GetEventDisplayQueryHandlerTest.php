<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventDisplayQuery;
use App\Features\Events\Queries\GetEventDisplayQueryHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    private int $organization_sequence = 1;

    public function test_invoke_returns_event_with_shifts_and_sorted_troopers(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $viewer = Trooper::factory()->asMember()->create();
        $later = Trooper::factory()->asMember()->create();
        $earlier = Trooper::factory()->asMember()->create();

        EventTrooper::factory()->forEventShift($shift)->forTrooper($later)->withSignedUpAt(Carbon::parse('2026-03-10 11:00:00'))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($earlier)->withSignedUpAt(Carbon::parse('2026-03-10 10:00:00'))->create();

        $subject = new GetEventDisplayQueryHandler;

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $this->assertSame($event->id, $result->id);
        $this->assertCount(1, $result->event_shifts);
        $this->assertSame($earlier->id, $result->event_shifts->first()->event_troopers->first()->trooper_id);
    }

    public function test_invoke_returns_event_with_guests_sorted_by_name_and_loaded_submitters(): void
    {
        $event = Event::factory()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $viewer = Trooper::factory()->asMember()->create();
        $first_submitter = Trooper::factory()->asMember()->withDisplayName('Alpha Submitter')->create();
        $second_submitter = Trooper::factory()->asMember()->withDisplayName('Zulu Submitter')->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($second_submitter)
            ->withName('Zed Guest')
            ->create();

        EventGuest::factory()
            ->forEventShift($shift)
            ->forTrooper($first_submitter)
            ->withName('Ahsoka Guest')
            ->create();

        $subject = new GetEventDisplayQueryHandler;

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $guest_names = $result->event_shifts->first()->event_guests->pluck(EventGuest::NAME)->all();

        $this->assertSame(['Ahsoka Guest', 'Zed Guest'], $guest_names);
        $this->assertTrue(
            $result->event_shifts->first()->event_guests->first()->relationLoaded('added_by_trooper')
        );
        $this->assertSame(
            'Alpha Submitter',
            $result->event_shifts->first()->event_guests->first()->added_by_trooper->display_name
        );
    }

    public function test_invoke_computes_costume_organizations_and_backup_organizations(): void
    {
        $alpha = $this->create_organization('Alpha Outpost');
        $beta = $this->create_organization('Beta Base');
        $gamma = $this->create_organization('Gamma Garrison');

        $event = Event::factory()->withOrganization($alpha)->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $viewer = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $primary_costume = Costume::factory()->withName('TK Armor')->create();
        $backup_costume = Costume::factory()->withName('Officer Blacks')->create();

        $this->allow_organization_for_event($event, $alpha);
        $this->allow_organization_for_event($event, $beta);
        $this->allow_organization_for_event($event, $gamma);

        $this->join_organization($trooper, $alpha);
        $this->join_organization($trooper, $beta);

        $this->approve_costume_for_organization($trooper, $alpha, $primary_costume);
        $this->approve_costume_for_organization($trooper, $beta, $primary_costume);
        $this->approve_costume_for_organization($trooper, $beta, $backup_costume);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($primary_costume)
            ->withBackupCostume($backup_costume)
            ->create();

        $subject = new GetEventDisplayQueryHandler;

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $event_trooper = $result->event_shifts->first()->event_troopers->first();

        $this->assertSame('(*) Alpha Outpost, Beta Base', $event_trooper->costume_organizations);
        $this->assertSame('Beta Base', $event_trooper->backup_costume_organizations);
    }

    public function test_invoke_prefers_signup_organization_id_over_full_approved_org_set(): void
    {
        $alpha = $this->create_organization('Alpha Outpost');
        $beta = $this->create_organization('Beta Base');

        $event = Event::factory()->withOrganization($alpha)->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $viewer = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->withName('TK Armor')->create();

        $this->allow_organization_for_event($event, $alpha);
        $this->allow_organization_for_event($event, $beta);

        $this->join_organization($trooper, $alpha);
        $this->join_organization($trooper, $beta);

        $this->approve_costume_for_organization($trooper, $alpha, $costume);
        $this->approve_costume_for_organization($trooper, $beta, $costume);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->state([EventTrooper::ORGANIZATION_ID => $alpha->id])
            ->create();

        $subject = new GetEventDisplayQueryHandler;

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $this->assertSame(
            'Alpha Outpost',
            $result->event_shifts->first()->event_troopers->first()->costume_organizations
        );
    }

    public function test_invoke_excludes_approved_orgs_the_trooper_never_joined(): void
    {
        $alpha = $this->create_organization('Alpha Outpost');
        $beta = $this->create_organization('Beta Base');

        $event = Event::factory()->withOrganization($alpha)->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $viewer = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->withName('Command Staff')->create();

        $this->allow_organization_for_event($event, $alpha);
        $this->allow_organization_for_event($event, $beta);

        $this->join_organization($trooper, $alpha);
        // Approved to wear this costume for Beta despite never having joined Beta.
        $this->approve_costume_for_organization($trooper, $alpha, $costume);
        $this->approve_costume_for_organization($trooper, $beta, $costume);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->create();

        $subject = new GetEventDisplayQueryHandler;

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $this->assertSame(
            'Alpha Outpost',
            $result->event_shifts->first()->event_troopers->first()->costume_organizations
        );
    }

    public function test_invoke_marks_costume_as_unattached_when_no_approved_organizations_match(): void
    {
        $alpha = $this->create_organization('Alpha Outpost');
        $event = Event::factory()->withOrganization($alpha)->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        $viewer = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asMember()->create();
        $costume = Costume::factory()->withName('Scout Trooper')->create();

        $this->allow_organization_for_event($event, $alpha);

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->create();

        $subject = new GetEventDisplayQueryHandler;

        $result = $subject(new GetEventDisplayQuery($event, $viewer));

        $this->assertSame(
            '(unattached)',
            $result->event_shifts->first()->event_troopers->first()->costume_organizations
        );
    }

    private function create_organization(string $name): Organization
    {
        $sequence = str_pad((string) $this->organization_sequence, 3, '0', STR_PAD_LEFT);

        $this->organization_sequence++;

        return Organization::factory()
            ->asOrganization()
            ->withName($name)
            ->withNodePath($sequence.'.')
            ->create();
    }

    private function join_organization(Trooper $trooper, Organization $organization): void
    {
        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->create();
    }

    private function approve_costume_for_organization(
        Trooper $trooper,
        Organization $organization,
        Costume $costume,
    ): void {
        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($organization_costume)
            ->create();
    }

    private function allow_organization_for_event(Event $event, Organization $organization): void
    {
        EventOrganization::factory()
            ->forEvent($event)
            ->forOrganization($organization)
            ->canAttend()
            ->create();
    }
}
