<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommand;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommandHandler;
use App\Jobs\SendTrooperMilestoneNotificationsJob;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

/**
 * @see RecalculateTrooperRankCommandHandler
 */
class RecalculateTrooperRankCommandHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_shift_count_achievement_for_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_invoke_updates_existing_achievement(): void
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

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 2,
        ]);
        $this->assertDatabaseCount('tt_trooper_achievements', 7);
    }

    public function test_invoke_only_counts_attended_events(): void
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

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_invoke_processes_all_troopers_when_trooper_id_is_null(): void
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

        $command = new RecalculateTrooperRankCommand();
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper1->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
        ]);
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper2->id,
            TrooperAchievement::TYPE => AchievementType::TROOPER_SHIFTS->value,
        ]);
    }

    public function test_invoke_creates_milestone_achievements(): void
    {
        $trooper = Trooper::factory()->create();
        $event = Event::factory()->asClosed()->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => null]);

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_invoke_dispatches_milestone_notification_job_for_new_milestone(): void
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

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        Bus::assertDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_invoke_can_create_global_milestone_without_dispatching_notification_job(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $summary = $handler(new RecalculateTrooperRankCommand(
            trooper_id: $trooper->id,
            send_milestone_notifications: false,
        ));

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

    public function test_invoke_does_not_dispatch_milestone_notification_job_for_existing_milestone(): void
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
            TrooperAchievement::TYPE       => AchievementType::FIRST_TROOP,
            TrooperAchievement::VALUE      => true,
            TrooperAchievement::NOTIFICATION_SENT_AT => now(),
        ]);

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        Bus::assertNotDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_invoke_dispatches_milestone_notification_job_for_existing_unsent_milestone(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $summary = $handler(new RecalculateTrooperRankCommand(trooper_id: $trooper->id));

        $this->assertSame(0, $summary['created_milestones']['total']);
        Bus::assertDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_invoke_creates_club_milestone_from_explicit_organization_id(): void
    {
        $trooper = Trooper::factory()->create();
        $club = $this->createClub('501st Legion');
        $shift = $this->createClosedShift();

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create([EventTrooper::ORGANIZATION_ID => $club->id]);

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $handler(new RecalculateTrooperRankCommand(trooper_id: $trooper->id));

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

    public function test_invoke_creates_club_milestone_from_costume_organization_ids(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $handler(new RecalculateTrooperRankCommand(trooper_id: $trooper->id));

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::ORGANIZATION_ID => $club->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }

    public function test_invoke_prefers_costume_credit_over_explicit_capacity_org_for_club_milestones(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $handler(new RecalculateTrooperRankCommand(trooper_id: $trooper->id));

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

    public function test_invoke_does_not_double_count_same_shift_for_same_club(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $handler(new RecalculateTrooperRankCommand(trooper_id: $trooper->id));

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

    public function test_invoke_dispatches_milestone_notification_job_for_new_club_milestone(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $handler(new RecalculateTrooperRankCommand(trooper_id: $trooper->id));

        Bus::assertDispatched(SendTrooperMilestoneNotificationsJob::class);
    }

    public function test_invoke_can_create_club_milestone_without_dispatching_notification_job(): void
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

        $handler = app(RecalculateTrooperRankCommandHandler::class);
        $summary = $handler(new RecalculateTrooperRankCommand(
            trooper_id: $trooper->id,
            send_milestone_notifications: false,
        ));

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
}
