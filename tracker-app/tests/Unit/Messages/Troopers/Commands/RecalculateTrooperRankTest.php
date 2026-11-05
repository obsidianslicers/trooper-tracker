<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands;

use App\Contracts\DonationSource;
use App\Enums\AchievementType;
use App\Enums\EventTrooperStatus;
use App\Jobs\SendTrooperMilestoneNotificationsJob;
use App\Messages\Troopers\Commands\RecalculateTrooperRank;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use App\Models\TrooperDonation;
use App\Plugins\PluginDonations;
use App\Support\DonationSummary;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Mockery;
use Tests\TestCase;

class RecalculateTrooperRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_creates_shift_count_achievement_for_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $command = new RecalculateTrooperRank(trooper_id: $trooper->id);

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_creates_shift_count_achievement_for_all_troopers(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $command = new RecalculateTrooperRank;

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_creates_shift_count_achievement_for_all_troopers_with_an_organization(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => $organization->id]);

        $command = new RecalculateTrooperRank;

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_updates_existing_achievement(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift1)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift2)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        TrooperAchievement::factory()
            ->create([
                TrooperAchievement::TROOPER_ID => $trooper->id,
                TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS,
                TrooperAchievement::VALUE => 1,
            ]);

        $command = new RecalculateTrooperRank(trooper_id: $trooper->id);

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 2,
        ]);
        $this->assertDatabaseCount('tt_trooper_achievements', 7);
    }

    public function test_handle_only_counts_attended_events(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift1)
            ->asAttended()
            ->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift2)
            ->create([EventTrooper::STATUS => EventTrooperStatus::GOING]);

        $command = new RecalculateTrooperRank(trooper_id: $trooper->id);

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_processes_all_troopers_when_trooper_id_is_null(): void
    {
        $trooper1 = Trooper::factory()->create();
        $trooper2 = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift1 = EventShift::factory()->forEvent($event)->create();
        $shift2 = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forTrooper($trooper1)
            ->forEventShift($shift1)
            ->asAttended()
            ->create();
        EventTrooper::factory()
            ->forTrooper($trooper2)
            ->forEventShift($shift2)
            ->asAttended()
            ->create();

        $command = new RecalculateTrooperRank;

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper1->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper2->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
        ]);
    }

    public function test_handle_creates_milestone_achievements(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $command = new RecalculateTrooperRank(trooper_id: $trooper->id);

        $command->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_leaves_new_milestone_pending_for_daily_roundup(): void
    {
        Bus::fake();

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $command = new RecalculateTrooperRank(trooper_id: $trooper->id);

        $command->handle();

        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
        $this->assertNull(TrooperAchievement::where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::TYPE, AchievementType::FIRST_TROOP)
            ->value(TrooperAchievement::NOTIFICATION_SENT_AT));
    }

    public function test_handle_can_create_global_milestone_without_dispatching_notification_job(): void
    {
        Bus::fake();

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $summary = (new RecalculateTrooperRank(
            trooper_id: $trooper->id,
            send_milestone_notifications: false,
        ))->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
        $this->assertSame(1, $summary['created_milestones']['total']);
        $this->assertSame(1, $summary['created_milestones']['global']);
        $this->assertSame(0, $summary['created_milestones']['club']);
        $this->assertNotNull(TrooperAchievement::where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::TYPE, AchievementType::FIRST_TROOP)
            ->value(TrooperAchievement::NOTIFICATION_SENT_AT));
        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_handle_does_not_dispatch_milestone_notification_job_for_existing_milestone(): void
    {
        Bus::fake();

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
            TrooperAchievement::VALUE => true,
            TrooperAchievement::NOTIFICATION_SENT_AT => now(),
        ]);

        $command = new RecalculateTrooperRank(trooper_id: $trooper->id);

        $command->handle();

        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_handle_leaves_existing_unsent_milestone_pending_for_daily_roundup(): void
    {
        Bus::fake();

        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        TrooperAchievement::factory()->create([
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP,
            TrooperAchievement::VALUE => true,
            TrooperAchievement::NOTIFICATION_SENT_AT => null,
        ]);

        $summary = (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        $this->assertSame(0, $summary['created_milestones']['total']);
        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
        $this->assertNull(TrooperAchievement::where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::TYPE, AchievementType::FIRST_TROOP)
            ->value(TrooperAchievement::NOTIFICATION_SENT_AT));
    }

    public function test_handle_creates_club_milestone_from_explicit_organization_id(): void
    {
        $trooper = Trooper::factory()->create();
        $club = $this->createClub('501st Legion');
        $shift = $this->createClosedShift();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => $club->id]);

        (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => null,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_creates_club_milestone_from_costume_organization_ids(): void
    {
        $trooper = Trooper::factory()->create();
        $club = $this->createClub('Rebel Legion');
        $region = $this->createRegion($club, 'Ra Kura Base');
        $shift = $this->createClosedShift();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->withCostumeOrganizationIds([$region->id])
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_handle_prefers_costume_credit_over_explicit_capacity_org_for_club_milestones(): void
    {
        $trooper = Trooper::factory()->create();
        $capacity_club = $this->createClub('501st Legion');
        $credit_club = $this->createClub('Rebel Legion');
        $shift = $this->createClosedShift();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->withCostumeOrganizationIds([$credit_club->id])
            ->create([EventTrooper::ORGANIZATION_ID => $capacity_club->id]);

        (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        $this->assertDatabaseMissing('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $capacity_club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $credit_club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
        ]);
    }

    public function test_handle_does_not_double_count_same_shift_for_same_club(): void
    {
        $trooper = Trooper::factory()->create();
        $club = $this->createClub('501st Legion');
        $region = $this->createRegion($club, 'Florida Garrison');

        for ($i = 0; $i < 5; $i++)
        {
            EventTrooper::factory()
                ->forTrooper($trooper)
                ->forEventShift($this->createClosedShift())
                ->asAttended()
                ->withCostumeOrganizationIds([$club->id, $region->id])
                ->create([EventTrooper::ORGANIZATION_ID => null]);
        }

        (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
        ]);
        $this->assertDatabaseMissing('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $club->id,
            TrooperAchievement::TYPE => AchievementType::TROOPED_10->value,
        ]);
    }

    public function test_handle_leaves_new_club_milestone_pending_for_daily_roundup(): void
    {
        Bus::fake();

        $trooper = Trooper::factory()->create();
        $club = $this->createClub('501st Legion');
        $shift = $this->createClosedShift();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => $club->id]);

        (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_handle_can_create_club_milestone_without_dispatching_notification_job(): void
    {
        Bus::fake();

        $trooper = Trooper::factory()->create();
        $club = $this->createClub('501st Legion');
        $shift = $this->createClosedShift();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => $club->id]);

        $summary = (new RecalculateTrooperRank(
            trooper_id: $trooper->id,
            send_milestone_notifications: false,
        ))->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
        $this->assertSame(2, $summary['created_milestones']['total']);
        $this->assertSame(1, $summary['created_milestones']['global']);
        $this->assertSame(1, $summary['created_milestones']['club']);
        $this->assertSame(2, $summary['created_milestones']['by_type'][AchievementType::FIRST_TROOP->value]);
        $this->assertNotNull(TrooperAchievement::where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::ORGANIZATION_ID, $club->id)
            ->where(TrooperAchievement::TYPE, AchievementType::FIRST_TROOP)
            ->value(TrooperAchievement::NOTIFICATION_SENT_AT));
        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    private function createClub(string $name): Organization
    {
        $club = Organization::factory()->asOrganization()->withName($name)->create();
        $club->updateQuietly([Organization::NODE_PATH => (string) $club->id]);

        return $club;
    }

    private function createRegion(Organization $club, string $name): Organization
    {
        $region = Organization::factory()
            ->asRegion()
            ->withName($name)
            ->withParent($club)
            ->create();
        $region->updateQuietly([
            Organization::NODE_PATH => $club->id.Organization::NODE_PATH_SEP.$region->id,
        ]);

        return $region;
    }

    private function createClosedShift(): EventShift
    {
        $event = Event::factory()->asClosed()->create();

        return EventShift::factory()->forEvent($event)->create();
    }

    public function test_handle_adds_donation_source_months_and_total_to_local_donations(): void
    {
        $trooper = Trooper::factory()->create();
        TrooperDonation::factory()->create([
            TrooperDonation::TROOPER_ID => $trooper->id,
            TrooperDonation::AMOUNT => 40,
            TrooperDonation::CREATED_AT => '2025-01-15 12:00:00',
        ]);

        $source = Mockery::mock(DonationSource::class);
        $source->shouldReceive('summariesFor')
            ->once()
            ->andReturn([$trooper->id => new DonationSummary(
                month_keys: ['2025-01' => true, '2025-02' => true],
                total: 75.0,
            )]);
        $this->app->instance($source::class, $source);
        $this->app->make(PluginDonations::class)->add($source::class);

        (new RecalculateTrooperRank(trooper_id: $trooper->id))->handle();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::DONATION_MONTHS->value,
            TrooperAchievement::VALUE => 2,
        ]);
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TOTAL_DONATED->value,
            TrooperAchievement::VALUE => 115,
        ]);
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::DONATED_100->value,
        ]);
    }

    public function test_handle_counts_local_donation_months_and_amount_without_donation_sources(): void
    {
        $trooper = Trooper::factory()->create();
        TrooperDonation::factory()->forTrooper($trooper)->createdAt(Carbon::parse('2026-01-05'))
            ->create([TrooperDonation::AMOUNT => 200]);
        TrooperDonation::factory()->forTrooper($trooper)->createdAt(Carbon::parse('2026-03-05'))
            ->create([TrooperDonation::AMOUNT => 100]);

        $subject = new RecalculateTrooperRank(
            trooper_id: $trooper->id,
            send_milestone_notifications: false,
        );
        $subject->handle();

        $this->assertAchievementValue($trooper, AchievementType::DONATION_MONTHS, 2);
        $this->assertAchievementValue($trooper, AchievementType::TOTAL_DONATED, 300);
        $this->assertHasAchievement($trooper, AchievementType::DONATED_250);
        $this->assertMissingAchievement($trooper, AchievementType::DONATED_500);
        $this->assertMissingAchievement($trooper, AchievementType::SUPPORTER_12_MONTHS);
    }

    private function achievementValue(Trooper $trooper, AchievementType $type): mixed
    {
        return TrooperAchievement::query()
            ->where(TrooperAchievement::TROOPER_ID, $trooper->id)
            ->where(TrooperAchievement::TYPE, $type->value)
            ->first()
            ?->value;
    }

    private function assertAchievementValue(Trooper $trooper, AchievementType $type, int $value): void
    {
        $this->assertEquals($value, $this->achievementValue($trooper, $type));
    }

    private function assertHasAchievement(Trooper $trooper, AchievementType $type): void
    {
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => $type->value,
        ]);
    }

    private function assertMissingAchievement(Trooper $trooper, AchievementType $type): void
    {
        $this->assertDatabaseMissing('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => $type->value,
        ]);
    }
}
