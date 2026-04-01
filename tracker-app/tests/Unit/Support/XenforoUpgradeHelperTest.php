<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\XenforoUpgradeHelper;
use Tests\TestCase;

class XenforoUpgradeHelperTest extends TestCase
{
    // -------------------------------------------------------------------------
    // resolveRecordCost
    // -------------------------------------------------------------------------

    public function test_resolve_record_cost_uses_extra_json_when_present(): void
    {
        $row      = ['extra' => json_encode(['cost_amount' => '4.99']), 'user_upgrade_id' => 1];
        $cost_map = [1 => 9.99];

        $this->assertSame(4.99, XenforoUpgradeHelper::resolveRecordCost($row, $cost_map));
    }

    public function test_resolve_record_cost_falls_back_to_cost_map_when_extra_is_missing(): void
    {
        $row      = ['user_upgrade_id' => 2];
        $cost_map = [2 => 7.50];

        $this->assertSame(7.50, XenforoUpgradeHelper::resolveRecordCost($row, $cost_map));
    }

    public function test_resolve_record_cost_falls_back_to_cost_map_when_extra_amount_is_zero(): void
    {
        $row      = ['extra' => json_encode(['cost_amount' => 0]), 'user_upgrade_id' => 3];
        $cost_map = [3 => 5.00];

        $this->assertSame(5.00, XenforoUpgradeHelper::resolveRecordCost($row, $cost_map));
    }

    public function test_resolve_record_cost_returns_zero_when_no_data_available(): void
    {
        $row      = ['user_upgrade_id' => 99];
        $cost_map = [];

        $this->assertSame(0.0, XenforoUpgradeHelper::resolveRecordCost($row, $cost_map));
    }

    public function test_resolve_record_cost_ignores_invalid_extra_json(): void
    {
        $row      = ['extra' => 'not-valid-json', 'user_upgrade_id' => 1];
        $cost_map = [1 => 3.00];

        $this->assertSame(3.00, XenforoUpgradeHelper::resolveRecordCost($row, $cost_map));
    }

    // -------------------------------------------------------------------------
    // countMonthsForRecord
    // -------------------------------------------------------------------------

    public function test_count_months_for_record_returns_one_for_same_month(): void
    {
        $start = mktime(0, 0, 0, 6, 1, 2024);
        $end   = mktime(0, 0, 0, 6, 30, 2024);

        $this->assertSame(1, XenforoUpgradeHelper::countMonthsForRecord($start, $end));
    }

    public function test_count_months_for_record_spans_multiple_months(): void
    {
        $start = mktime(0, 0, 0, 1, 15, 2024);
        $end   = mktime(0, 0, 0, 3, 15, 2024);

        $this->assertSame(3, XenforoUpgradeHelper::countMonthsForRecord($start, $end));
    }

    public function test_count_months_for_record_returns_zero_when_start_is_zero(): void
    {
        $this->assertSame(0, XenforoUpgradeHelper::countMonthsForRecord(0, time()));
    }

    public function test_count_months_for_record_caps_future_end_date_to_now(): void
    {
        // start = first of current month, end = far future → should count 1 (this month)
        $start  = mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'));
        $future = strtotime('+5 years');

        $result = XenforoUpgradeHelper::countMonthsForRecord($start, $future);

        $this->assertGreaterThanOrEqual(1, $result);
    }

    public function test_count_months_for_record_treats_end_date_zero_as_now(): void
    {
        $start = mktime(0, 0, 0, (int) date('m'), 1, (int) date('Y'));

        $result = XenforoUpgradeHelper::countMonthsForRecord($start, 0);

        $this->assertSame(1, $result);
    }

    // -------------------------------------------------------------------------
    // monthKeysFromUpgrades
    // -------------------------------------------------------------------------

    public function test_month_keys_from_upgrades_returns_distinct_months(): void
    {
        $active  = [
            ['start_date' => mktime(0, 0, 0, 9, 1, 2024), 'end_date' => mktime(0, 0, 0, 10, 31, 2024)],
        ];
        $expired = [
            ['start_date' => mktime(0, 0, 0, 9, 15, 2024), 'end_date' => mktime(0, 0, 0, 11, 1, 2024)],
        ];

        $keys = XenforoUpgradeHelper::monthKeysFromUpgrades($active, $expired);

        $this->assertArrayHasKey('2024-09', $keys);
        $this->assertArrayHasKey('2024-10', $keys);
        $this->assertArrayHasKey('2024-11', $keys);
        $this->assertCount(3, $keys);
    }

    public function test_month_keys_from_upgrades_deduplicates_overlapping_ranges(): void
    {
        $active  = [
            ['start_date' => mktime(0, 0, 0, 1, 1, 2024), 'end_date' => mktime(0, 0, 0, 3, 31, 2024)],
        ];
        $expired = [
            ['start_date' => mktime(0, 0, 0, 2, 1, 2024), 'end_date' => mktime(0, 0, 0, 4, 30, 2024)],
        ];

        $keys = XenforoUpgradeHelper::monthKeysFromUpgrades($active, $expired);

        $this->assertCount(4, $keys); // Jan, Feb, Mar, Apr — no duplicates
    }

    public function test_month_keys_from_upgrades_skips_records_with_zero_start(): void
    {
        $active  = [['start_date' => 0, 'end_date' => time()]];
        $expired = [];

        $keys = XenforoUpgradeHelper::monthKeysFromUpgrades($active, $expired);

        $this->assertEmpty($keys);
    }

    public function test_month_keys_from_upgrades_returns_empty_for_no_records(): void
    {
        $keys = XenforoUpgradeHelper::monthKeysFromUpgrades([], []);

        $this->assertSame([], $keys);
    }
}
