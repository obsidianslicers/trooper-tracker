<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Enums\AchievementType;
use App\Messages\Troopers\Commands\Merge\MergeTrooperAchievements;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAchievement;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeTrooperAchievementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_achievements_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_achievement = TrooperAchievement::factory()
            ->forTrooper($source_trooper)
            ->withType(AchievementType::FIRST_TROOP)
            ->create();

        $trashed_achievement = TrooperAchievement::factory()
            ->forTrooper($source_trooper)
            ->withType(AchievementType::DONATED_100)
            ->create();
        $trashed_achievement->delete();

        MergeTrooperAchievements::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::ID => $active_achievement->id,
            TrooperAchievement::TROOPER_ID => $target_trooper->id,
            TrooperAchievement::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_trooper_achievements', [
            TrooperAchievement::ID => $trashed_achievement->id,
            TrooperAchievement::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_achievements', [
            TrooperAchievement::ID => $active_achievement->id,
            TrooperAchievement::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_achievements', [
            TrooperAchievement::ID => $trashed_achievement->id,
            TrooperAchievement::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_merges_duplicate_scoped_achievement_and_restores_target(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $target_achievement = TrooperAchievement::factory()
            ->forTrooper($target_trooper)
            ->forOrganization($organization)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([
                TrooperAchievement::VALUE => null,
                TrooperAchievement::ACHIEVEMENT_DATE => Carbon::parse('2026-07-15 00:00:00'),
                TrooperAchievement::NOTIFICATION_SENT_AT => Carbon::parse('2026-07-16 00:00:00'),
            ]);
        $target_achievement->delete();

        $source_achievement = TrooperAchievement::factory()
            ->forTrooper($source_trooper)
            ->forOrganization($organization)
            ->withType(AchievementType::FIRST_TROOP)
            ->create([
                TrooperAchievement::VALUE => true,
                TrooperAchievement::ACHIEVEMENT_DATE => Carbon::parse('2026-07-10 00:00:00'),
                TrooperAchievement::NOTIFICATION_SENT_AT => Carbon::parse('2026-07-20 00:00:00'),
            ]);

        MergeTrooperAchievements::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $target_achievement->refresh();

        $this->assertDatabaseHas('tt_trooper_achievements', [
            TrooperAchievement::ID => $target_achievement->id,
            TrooperAchievement::TROOPER_ID => $target_trooper->id,
            TrooperAchievement::TYPE => AchievementType::FIRST_TROOP->value,
            TrooperAchievement::ORGANIZATION_ID => $organization->id,
            TrooperAchievement::DELETED_AT => null,
        ]);

        $this->assertDatabaseMissing('tt_trooper_achievements', [
            TrooperAchievement::ID => $source_achievement->id,
        ]);

        $this->assertTrue((bool) $target_achievement->{TrooperAchievement::VALUE});
        $this->assertTrue(
            $target_achievement->{TrooperAchievement::ACHIEVEMENT_DATE}
                    ?->equalTo($source_achievement->{TrooperAchievement::ACHIEVEMENT_DATE}),
        );
        $this->assertTrue(
            $target_achievement->{TrooperAchievement::NOTIFICATION_SENT_AT}
                    ?->equalTo($source_achievement->{TrooperAchievement::NOTIFICATION_SENT_AT}),
        );
        $this->assertSame(
            1,
            TrooperAchievement::query()
                ->where(TrooperAchievement::TROOPER_ID, $target_trooper->id)
                ->where(TrooperAchievement::TYPE, AchievementType::FIRST_TROOP->value)
                ->where(TrooperAchievement::ORGANIZATION_COALESCE_ID, $organization->id)
                ->count(),
        );
    }
}
