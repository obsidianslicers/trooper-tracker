<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Troopers\Commands;

use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommand;
use App\Features\Troopers\Commands\RecalculateTrooperRankCommandHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->create();

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
            ->create();
        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift2)
            ->asAttended()
            ->create();

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
        $this->assertDatabaseCount('tt_trooper_achievements', 5);
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
            ->create();

        $command = new RecalculateTrooperRankCommand(trooper_id: $trooper->id);
        $handler = app(RecalculateTrooperRankCommandHandler::class);

        $handler($command);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::VALUE => 1,
        ]);
    }
}
