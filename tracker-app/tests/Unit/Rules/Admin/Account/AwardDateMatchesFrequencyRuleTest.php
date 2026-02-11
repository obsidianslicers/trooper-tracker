<?php

declare(strict_types=1);

namespace Tests\Unit\Rules\Admin\Account;

use App\Enums\AwardFrequency;
use App\Rules\Admin\Account\AwardDateMatchesFrequencyRule;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Unit tests for AwardDateMatchesFrequencyRule.
 *
 * Verifies validation of award dates against frequency schedules:
 * - Monthly awards use the first day of the month
 * - Quarterly awards use the first day of the quarter
 * - Annual awards use January 1st
 * - Random and once-only awards accept any date
 */
class AwardDateMatchesFrequencyRuleTest extends TestCase
{
    #[DataProvider('validMonthlyDatesProvider')]
    public function test_passes_with_valid_monthly_dates(Carbon $date): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::MONTHLY);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed with: ' . $message);
        };

        $subject->validate('award_date', $date->toDateString(), $fail);

        $this->assertFalse($fail_was_called);
    }

    #[DataProvider('invalidMonthlyDatesProvider')]
    public function test_fails_with_invalid_monthly_dates(Carbon $date): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::MONTHLY);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->assertStringContainsString('first day of the month', $message);
        };

        $subject->validate('award_date', $date->toDateString(), $fail);

        $this->assertTrue($fail_was_called);
    }

    #[DataProvider('validQuarterlyDatesProvider')]
    public function test_passes_with_valid_quarterly_dates(Carbon $date): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::QUARTERLY);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed with: ' . $message);
        };

        $subject->validate('award_date', $date->toDateString(), $fail);

        $this->assertFalse($fail_was_called);
    }

    #[DataProvider('invalidQuarterlyDatesProvider')]
    public function test_fails_with_invalid_quarterly_dates(Carbon $date): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::QUARTERLY);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->assertStringContainsString('first day of the quarter', $message);
        };

        $subject->validate('award_date', $date->toDateString(), $fail);

        $this->assertTrue($fail_was_called);
    }

    #[DataProvider('validAnnualDatesProvider')]
    public function test_passes_with_valid_annual_dates(Carbon $date): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::ANNUALLY);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed but failed with: ' . $message);
        };

        $subject->validate('award_date', $date->toDateString(), $fail);

        $this->assertFalse($fail_was_called);
    }

    #[DataProvider('invalidAnnualDatesProvider')]
    public function test_fails_with_invalid_annual_dates(Carbon $date): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::ANNUALLY);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->assertStringContainsString('January 1st', $message);
        };

        $subject->validate('award_date', $date->toDateString(), $fail);

        $this->assertTrue($fail_was_called);
    }

    public function test_passes_with_any_date_for_once_frequency(): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::ONCE);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed for ONCE frequency');
        };

        $subject->validate('award_date', '2026-02-15', $fail);

        $this->assertFalse($fail_was_called);
    }

    public function test_passes_with_any_date_for_random_frequency(): void
    {
        $subject = new AwardDateMatchesFrequencyRule(AwardFrequency::RANDOM);
        $fail_was_called = false;
        $fail = function (string $message) use (&$fail_was_called): void
        {
            $fail_was_called = true;
            $this->fail('Validation should have passed for RANDOM frequency');
        };

        $subject->validate('award_date', '2026-02-15', $fail);

        $this->assertFalse($fail_was_called);
    }

    public static function validMonthlyDatesProvider(): array
    {
        return [
            'first of January' => [Carbon::parse('2026-01-01')],
            'first of February' => [Carbon::parse('2026-02-01')],
            'first of December' => [Carbon::parse('2026-12-01')],
        ];
    }

    public static function invalidMonthlyDatesProvider(): array
    {
        return [
            'second of month' => [Carbon::parse('2026-02-02')],
            'mid month' => [Carbon::parse('2026-02-15')],
            'end of month' => [Carbon::parse('2026-02-28')],
        ];
    }

    public static function validQuarterlyDatesProvider(): array
    {
        return [
            '2026-01-01 (Q1)' => [Carbon::parse('2026-01-01')],
            '2026-04-01 (Q2)' => [Carbon::parse('2026-04-01')],
            '2026-07-01 (Q3)' => [Carbon::parse('2026-07-01')],
            '2026-10-01 (Q4)' => [Carbon::parse('2026-10-01')],
        ];
    }

    public static function invalidQuarterlyDatesProvider(): array
    {
        return [
            'second of quarter' => [Carbon::parse('2026-01-02')],
            'mid quarter' => [Carbon::parse('2026-02-15')],
            'random date' => [Carbon::parse('2026-03-31')],
        ];
    }

    public static function validAnnualDatesProvider(): array
    {
        return [
            'January 1st 2025' => [Carbon::parse('2025-01-01')],
            'January 1st 2026' => [Carbon::parse('2026-01-01')],
            'January 1st 2027' => [Carbon::parse('2027-01-01')],
        ];
    }

    public static function invalidAnnualDatesProvider(): array
    {
        return [
            'January 2nd' => [Carbon::parse('2026-01-02')],
            'February 1st' => [Carbon::parse('2026-02-01')],
            'random date' => [Carbon::parse('2026-06-15')],
        ];
    }
}
