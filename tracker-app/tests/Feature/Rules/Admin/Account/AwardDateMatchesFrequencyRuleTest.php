<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Account;

use App\Enums\AwardFrequency;
use App\Rules\Admin\Account\AwardDateMatchesFrequencyRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class AwardDateMatchesFrequencyRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_monthly_frequency_requires_first_day_of_month(): void
    {
        $passes_validator = Validator::make([
            'award_date' => '2026-03-01',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::MONTHLY)],
        ]);

        $fails_validator = Validator::make([
            'award_date' => '2026-03-02',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::MONTHLY)],
        ]);

        $this->assertTrue($passes_validator->passes());
        $this->assertTrue($fails_validator->fails());
        $this->assertSame(
            'Monthly awards must use the first day of the month.',
            $fails_validator->errors()->first('award_date')
        );
    }

    public function test_quarterly_frequency_requires_first_day_of_quarter(): void
    {
        $passes_validator = Validator::make([
            'award_date' => '2026-04-01',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::QUARTERLY)],
        ]);

        $fails_validator = Validator::make([
            'award_date' => '2026-04-15',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::QUARTERLY)],
        ]);

        $this->assertTrue($passes_validator->passes());
        $this->assertTrue($fails_validator->fails());
        $this->assertSame(
            'Quarterly awards must use the first day of the quarter.',
            $fails_validator->errors()->first('award_date')
        );
    }

    public function test_annually_frequency_requires_january_first(): void
    {
        $passes_validator = Validator::make([
            'award_date' => '2026-01-01',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::ANNUALLY)],
        ]);

        $fails_validator = Validator::make([
            'award_date' => '2026-01-02',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::ANNUALLY)],
        ]);

        $this->assertTrue($passes_validator->passes());
        $this->assertTrue($fails_validator->fails());
        $this->assertSame(
            'Annual awards must use January 1st.',
            $fails_validator->errors()->first('award_date')
        );
    }

    public function test_once_and_random_frequencies_accept_any_date(): void
    {
        $once_validator = Validator::make([
            'award_date' => '2026-05-17',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::ONCE)],
        ]);

        $random_validator = Validator::make([
            'award_date' => '2026-08-29',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::RANDOM)],
        ]);

        $this->assertTrue($once_validator->passes());
        $this->assertTrue($random_validator->passes());
    }

    public function test_invalid_date_is_ignored_by_this_rule(): void
    {
        $validator = Validator::make([
            'award_date' => 'not-a-date',
        ], [
            'award_date' => [new AwardDateMatchesFrequencyRule(AwardFrequency::MONTHLY)],
        ]);

        $this->assertTrue($validator->passes());
    }
}
