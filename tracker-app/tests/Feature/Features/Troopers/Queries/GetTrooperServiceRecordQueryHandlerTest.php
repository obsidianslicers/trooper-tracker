<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Queries;

use App\Enums\AchievementType;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQuery;
use App\Features\Troopers\Queries\GetTrooperServiceRecordQueryHandler;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\EventUpload;
use App\Models\EventUploadTrooper;
use App\Models\Organization;
use App\Models\TrooperAchievement;
use App\Models\TrooperAssignment;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use App\Models\TrooperOrganization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperServiceRecordQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_full_service_record_payload(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $organization = Organization::factory()->asOrganization()->withNodePath('100.')->withName('Alpha')->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($organization)->withIdentifier('TK-1')->create();
        $assignment = TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->asMember()
            ->create();

        $event_upload = EventUpload::factory()->create();
        EventUploadTrooper::factory()->forEventUpload($event_upload)->forTrooper($trooper)->create();

        $event = Event::factory()->withOrganization($organization)->create();

        $upcoming_shift = EventShift::factory()
            ->forEvent($event)
            ->withShiftStartsAt(now()->addDays(2))
            ->create();
        EventTrooper::factory()->forEventShift($upcoming_shift)->forTrooper($trooper)->create();

        $recent_shift = EventShift::factory()
            ->forEvent($event)
            ->asClosed()
            ->withShiftStartsAt(now()->subDays(30))
            ->create();
        EventTrooper::factory()->forEventShift($recent_shift)->forTrooper($trooper)->create();

        TrooperDonation::factory()->forTrooper($trooper)->createdAt(Carbon::now()->subDays(10))->create();

        $award = Award::factory()->create();
        AwardTrooper::factory()->forTrooper($trooper)->forAward($award)->create();

        $subject = new GetTrooperServiceRecordQueryHandler();

        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('trooper', $result);
        $this->assertArrayHasKey('trooper_organizations', $result);
        $this->assertArrayHasKey('tagged_uploads', $result);
        $this->assertArrayHasKey('service_summary', $result);
        $this->assertArrayHasKey('upcoming_shifts', $result);
        $this->assertArrayHasKey('recent_shifts', $result);
        $this->assertArrayHasKey('all_donations', $result);
        $this->assertArrayHasKey('awards', $result);
        $this->assertSame($trooper->id, $result['trooper']->id);
        $this->assertCount(1, $result['trooper_organizations']);
        $this->assertSame(
            $assignment->id,
            $result['trooper_organizations']->first()->assignment->id,
        );
        $this->assertCount(1, $result['tagged_uploads']);
        $this->assertCount(1, $result['all_donations']);
        $this->assertCount(1, $result['awards']);
    }

    // -------------------------------------------------------------------------
    // troop_count attribution
    // -------------------------------------------------------------------------

    public function test_invoke_troop_count_rule1_credits_org_from_explicit_organization_id(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org = Organization::factory()->asOrganization()->withNodePath('100')->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->withShiftStartsAt(now()->subDays(5))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()
            ->state(fn() => [EventTrooper::ORGANIZATION_ID => $org->id])->create();

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertSame(1, $result['trooper_organizations']->first()->troop_count);
    }

    public function test_invoke_troop_count_rule2_credits_org_from_costume_organization_ids(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org = Organization::factory()->asOrganization()->withNodePath('100')->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->withShiftStartsAt(now()->subDays(5))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create()
            ->updateQuietly([
                EventTrooper::ORGANIZATION_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org->id],
            ]);

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertSame(1, $result['trooper_organizations']->first()->troop_count);
    }

    public function test_invoke_troop_count_prefers_costume_organization_ids_over_capacity_org(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $capacity_org = Organization::factory()->asOrganization()->withNodePath('100')->create();
        $credit_org1 = Organization::factory()->asOrganization()->withNodePath('200')->create();
        $credit_org2 = Organization::factory()->asOrganization()->withNodePath('300')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($capacity_org)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($credit_org1)->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($credit_org2)->create();

        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->withShiftStartsAt(now()->subDays(5))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create()
            ->updateQuietly([
                EventTrooper::ORGANIZATION_ID => $capacity_org->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$credit_org1->id, $credit_org2->id],
            ]);

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));
        $counts = $result['trooper_organizations']->pluck('troop_count', 'id');

        $this->assertSame(0, $counts[$capacity_org->id]);
        $this->assertSame(1, $counts[$credit_org1->id]);
        $this->assertSame(1, $counts[$credit_org2->id]);
    }

    public function test_invoke_troop_count_unattached_shift_gives_no_org_credit(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org = Organization::factory()->asOrganization()->withNodePath('100')->create();
        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)->create();

        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()->withShiftStartsAt(now()->subDays(5))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create();

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertSame(0, $result['trooper_organizations']->first()->troop_count);
    }

    public function test_invoke_troop_count_join_date_after_shift_does_not_exclude_credit(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $org = Organization::factory()->asOrganization()->withNodePath('100')->create();

        TrooperOrganization::factory()->forTrooper($trooper)->forOrganization($org)
            ->state(fn() => [TrooperOrganization::JOIN_DATE => Carbon::parse('2026-06-01')])
            ->create();

        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->asClosed()
            ->withShiftStartsAt(Carbon::parse('2026-01-01'))->create();
        EventTrooper::factory()->forEventShift($shift)->forTrooper($trooper)->asAttended()->create()
            ->updateQuietly([
                EventTrooper::ORGANIZATION_ID => null,
                EventTrooper::COSTUME_ORGANIZATION_IDS => [$org->id],
            ]);

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertSame(1, $result['trooper_organizations']->first()->troop_count);
    }

    public function test_invoke_service_summary_includes_club_scoped_milestones(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withName('501st Legion')->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([TrooperAchievement::VALUE => true]);

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $descriptions = collect($result['service_summary']['milestones'])->pluck('description');

        $this->assertTrue($descriptions->contains('First Troop - Combat Readiness Citation'));
        $this->assertTrue($descriptions->contains('501st Legion: First Troop - Combat Readiness Citation'));
    }

    public function test_invoke_service_summary_groups_milestones_by_family(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $legion = Organization::factory()->asOrganization()->withName('501st Legion')->create();
        $rebel = Organization::factory()->asOrganization()->withName('Rebel Legion')->create();

        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($rebel)
            ->withType(AchievementType::TROOPED_10)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::DONATED_100)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($legion)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->withType(AchievementType::TROOPED_10)
            ->create([TrooperAchievement::VALUE => true]);
        TrooperAchievement::factory()
            ->forTrooper($trooper)
            ->forOrganization($legion)
            ->withType(AchievementType::TROOPED_10)
            ->create([TrooperAchievement::VALUE => true]);

        $subject = new GetTrooperServiceRecordQueryHandler();
        $result = $subject(new GetTrooperServiceRecordQuery($trooper->id));

        $this->assertSame([
            '10 Troops - Frontier Service Ribbon',
            'First Troop - Combat Readiness Citation',
            '501st Legion: 10 Troops - Frontier Service Ribbon',
            '501st Legion: First Troop - Combat Readiness Citation',
            'Rebel Legion: 10 Troops - Frontier Service Ribbon',
            '$100 Donated - Quartermaster\'s Commendation',
        ], collect($result['service_summary']['milestones'])->pluck('description')->all());
    }
}
