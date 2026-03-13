<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Account;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TrooperNotAlreadyAwardedRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_when_trooper_already_has_award_for_same_date(): void
    {
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->create();

        $awarded_record = AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($trooper)
            ->onDate('2026-01-01')
            ->create();

        $persisted_award_date = (string) $awarded_record->getRawOriginal(AwardTrooper::AWARD_DATE);

        $validator = Validator::make([
            'trooper_id' => $trooper->id,
        ], [
            'trooper_id' => [new TrooperNotAlreadyAwardedRule($award->id, $persisted_award_date)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'This trooper already has this award for the selected date.',
            $validator->errors()->first('trooper_id')
        );
    }

    public function test_passes_when_award_exists_for_different_trooper(): void
    {
        $award = Award::factory()->create();
        $awarded_trooper = Trooper::factory()->create();
        $candidate_trooper = Trooper::factory()->create();

        AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($awarded_trooper)
            ->onDate('2026-01-01')
            ->create();

        $validator = Validator::make([
            'trooper_id' => $candidate_trooper->id,
        ], [
            'trooper_id' => [new TrooperNotAlreadyAwardedRule($award->id, '2026-01-01')],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_passes_when_same_trooper_has_award_on_different_date(): void
    {
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->create();

        AwardTrooper::factory()
            ->forAward($award)
            ->forTrooper($trooper)
            ->onDate('2026-01-01')
            ->create();

        $validator = Validator::make([
            'trooper_id' => $trooper->id,
        ], [
            'trooper_id' => [new TrooperNotAlreadyAwardedRule($award->id, '2026-02-01')],
        ]);

        $this->assertTrue($validator->passes());
    }
}
