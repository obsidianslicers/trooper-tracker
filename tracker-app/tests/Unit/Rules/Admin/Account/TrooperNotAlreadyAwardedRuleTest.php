<?php

declare(strict_types=1);

namespace Tests\Unit\Rules\Admin\Account;

use App\Models\Award;
use App\Models\AwardTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Unit tests for TrooperNotAlreadyAwardedRule.
 *
 * Verifies that validation prevents duplicate awards:
 * - Fails if trooper already has award on the specified date
 * - Passes if trooper has not been awarded yet
 * - Passes if trooper has award but on different date
 */
class TrooperNotAlreadyAwardedRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_fails_if_trooper_already_awarded_on_date(): void
    {
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $award_date = now()->toDateString();

        // Create the duplicate record via raw SQL to bypass factory issues
        DB::table('tt_award_troopers')->insert([
            'award_id' => $award->id,
            'trooper_id' => $trooper->id,
            'award_date' => $award_date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test the rule - should detect the existing record and fail
        $subject = new \App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule(
            $award->id,
            $award_date
        );

        $fail_called = false;
        $fail_message = '';

        $subject->validate(AwardTrooper::TROOPER_ID, $trooper->id, function (string $message) use (&$fail_called, &$fail_message): void
        {
            $fail_called = true;
            $fail_message = $message;
        });

        $this->assertTrue($fail_called, 'Rule should fail when duplicate award exists');
        $this->assertStringContainsString('already has this award', $fail_message);
    }

    public function test_passes_if_trooper_not_yet_awarded(): void
    {
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $award_date = now()->toDateString();

        $subject = new \App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule(
            $award->id,
            $award_date
        );
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed: ' . $message);
        };

        $subject->validate('trooper_id', $trooper->id, $fail);

        $this->assertFalse($fail_was_called);
    }

    public function test_passes_if_trooper_awarded_on_different_date(): void
    {
        $award = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $old_date = now()->subDays(10)->toDateString();
        $new_date = now()->toDateString();

        AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => $old_date,
        ]);

        $subject = new \App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule(
            $award->id,
            $new_date
        );
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed: ' . $message);
        };

        $subject->validate('trooper_id', $trooper->id, $fail);

        $this->assertFalse($fail_was_called);
    }

    public function test_passes_if_different_trooper_has_award_on_date(): void
    {
        $award = Award::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();
        $award_date = now()->toDateString();

        AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award->id,
            AwardTrooper::TROOPER_ID => $trooper1->id,
            AwardTrooper::AWARD_DATE => $award_date,
        ]);

        $subject = new \App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule(
            $award->id,
            $award_date
        );
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed: ' . $message);
        };

        $subject->validate('trooper_id', $trooper2->id, $fail);

        $this->assertFalse($fail_was_called);
    }

    public function test_passes_if_different_award(): void
    {
        $award1 = Award::factory()->create();
        $award2 = Award::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $award_date = now()->toDateString();

        AwardTrooper::factory()->create([
            AwardTrooper::AWARD_ID => $award1->id,
            AwardTrooper::TROOPER_ID => $trooper->id,
            AwardTrooper::AWARD_DATE => $award_date,
        ]);

        $subject = new \App\Rules\Admin\Account\TrooperNotAlreadyAwardedRule(
            $award2->id,
            $award_date
        );
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed: ' . $message);
        };

        $subject->validate('trooper_id', $trooper->id, $fail);

        $this->assertFalse($fail_was_called);
    }
}
