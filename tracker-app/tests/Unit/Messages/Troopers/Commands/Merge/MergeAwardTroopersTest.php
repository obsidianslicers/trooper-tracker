<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeAwardTroopers;
use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeAwardTroopersTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_awards_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $active_award = Award::factory()->create();
        $trashed_award = Award::factory()->create();

        $active_award_trooper = AwardTrooper::factory()
            ->forAward($active_award)
            ->forTrooper($source_trooper)
            ->onDate('2026-07-01')
            ->create();

        $trashed_award_trooper = AwardTrooper::factory()
            ->forAward($trashed_award)
            ->forTrooper($source_trooper)
            ->onDate('2026-07-02')
            ->create();
        $trashed_award_trooper->delete();

        MergeAwardTroopers::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_award_troopers', [
            AwardTrooper::ID => $active_award_trooper->id,
            AwardTrooper::AWARD_ID => $active_award->id,
            AwardTrooper::TROOPER_ID => $target_trooper->id,
            AwardTrooper::AWARD_DATE => '2026-07-01 00:00:00',
            AwardTrooper::DELETED_AT => null,
        ]);

        $this->assertSoftDeleted('tt_award_troopers', [
            AwardTrooper::ID => $trashed_award_trooper->id,
            AwardTrooper::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_award_troopers', [
            AwardTrooper::ID => $active_award_trooper->id,
            AwardTrooper::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_award_troopers', [
            AwardTrooper::ID => $trashed_award_trooper->id,
            AwardTrooper::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_restores_target_award_when_source_has_matching_award_and_date(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $award = Award::factory()->create();

        $target_award_trooper = AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($target_trooper)
            ->onDate('2026-07-03')
            ->create();
        $target_award_trooper->delete();

        $source_award_trooper = AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($source_trooper)
            ->onDate('2026-07-03')
            ->create();

        MergeAwardTroopers::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_award_troopers', [
            AwardTrooper::ID => $target_award_trooper->id,
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $target_trooper->id,
            AwardTrooper::AWARD_DATE => '2026-07-03 00:00:00',
            AwardTrooper::DELETED_AT => null,
        ]);

        $this->assertDatabaseMissing('tt_award_troopers', [
            AwardTrooper::ID => $source_award_trooper->id,
            AwardTrooper::TROOPER_ID => $source_trooper->id,
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::AWARD_DATE => '2026-07-03 00:00:00',
        ]);
    }
}