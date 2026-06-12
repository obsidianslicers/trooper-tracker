<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\FloridaGarrison;

use App\Enums\AchievementType;
use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Database\Seeders\FloridaGarrison\TrooperAchievementSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TrooperAchievementSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_for_uses_shift_level_charity_metrics_after_event_charity_columns_are_removed(): void
    {
        $this->assertFalse(Schema::hasColumn('tt_events', 'charity_direct_funds'));

        $trooper = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        $event_start = Carbon::parse('2026-01-10 10:00:00');

        $event = Event::factory()
            ->withOrganization($organization)
            ->create([
                Event::STATUS => EventStatus::CLOSED,
                Event::EVENT_START => $event_start,
                Event::EVENT_END => $event_start->copy()->addHours(2),
            ]);

        $shift = EventShift::factory()
            ->forEvent($event)
            ->create([
                EventShift::STATUS => EventStatus::CLOSED,
                EventShift::SHIFT_STARTS_AT => $event_start,
                EventShift::SHIFT_ENDS_AT => $event_start->copy()->addHours(2),
                EventShift::CHARITY_DIRECT_FUNDS => 125,
                EventShift::CHARITY_INDIRECT_FUNDS => 75,
                EventShift::CHARITY_HOURS => 5,
            ]);

        EventTrooper::factory()
            ->forTrooper($trooper)
            ->forEventShift($shift)
            ->asAttended()
            ->create();

        (new TrooperAchievementSeeder())->runFor(
            Carbon::parse('2026-01-01'),
            Carbon::parse('2026-01-31')->endOfDay()
        );

        $this->assertAchievementValue($trooper, AchievementType::DIRECT_FUNDS, 125);
        $this->assertAchievementValue($trooper, AchievementType::INDIRECT_FUNDS, 75);
        $this->assertAchievementValue($trooper, AchievementType::VOLUNTEER_HOURS, 5);
    }

    private function assertAchievementValue(Trooper $trooper, AchievementType $type, mixed $value): void
    {
        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::TROOPER_ID => $trooper->id,
            TrooperAchievement::TYPE => $type->value,
            TrooperAchievement::VALUE => $value,
        ]);
    }
}
