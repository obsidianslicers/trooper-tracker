<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Seeders\FloridaGarrison;

use Database\Seeders\FloridaGarrison\Support\LegacyEventGroups;
use Tests\TestCase;

class LegacyEventGroupsTest extends TestCase
{
    public function test_linked_events_become_shifts_of_their_main_event(): void
    {
        config(['tracker.one_troop_per_day_from' => null]);

        $groups = LegacyEventGroups::group([
            $this->row(1, 0, '2026-12-05 10:00:00'),
            $this->row(2, 1, '2026-12-05 14:00:00'),
            $this->row(3, 1, '2026-12-06 10:00:00'),
            $this->row(4, 0, '2026-12-07 10:00:00'),
        ]);

        $this->assertSame([1 => [1, 2, 3], 4 => [4]], $this->ids($groups));
    }

    public function test_a_linked_event_on_a_day_its_group_already_has_stays_its_own_event_under_the_rule(): void
    {
        config(['tracker.one_troop_per_day_from' => '2000-01-01']);

        $groups = LegacyEventGroups::group([
            $this->row(1, 0, '2026-12-05 10:00:00'),
            $this->row(2, 1, '2026-12-05 14:00:00'),
            $this->row(3, 1, '2026-12-06 10:00:00'),
            $this->row(5, 1, '2026-12-06 14:00:00'),
        ]);

        $this->assertSame([1 => [1, 3], 2 => [2], 5 => [5]], $this->ids($groups));
    }

    public function test_a_linked_event_starting_the_same_minute_still_joins_its_group(): void
    {
        config(['tracker.one_troop_per_day_from' => '2000-01-01']);

        $groups = LegacyEventGroups::group([
            $this->row(1, 0, '2026-12-05 10:00:00'),
            $this->row(2, 1, '2026-12-05 10:00:00'),
        ]);

        $this->assertSame([1 => [1, 2]], $this->ids($groups));
    }

    public function test_same_day_linked_events_before_the_rule_date_stay_shifts(): void
    {
        config(['tracker.one_troop_per_day_from' => '2026-12-01']);

        $groups = LegacyEventGroups::group([
            $this->row(1, 0, '2026-11-20 10:00:00'),
            $this->row(2, 1, '2026-11-20 14:00:00'),
            $this->row(3, 0, '2026-12-05 10:00:00'),
            $this->row(4, 3, '2026-12-05 14:00:00'),
        ]);

        $this->assertSame([1 => [1, 2], 3 => [3], 4 => [4]], $this->ids($groups));
    }

    private function row(int $id, int $link, string $date_start): object
    {
        return (object) ['id' => $id, 'link' => $link, 'dateStart' => $date_start];
    }

    /**
     * @param  array<int, list<object>>  $groups
     * @return array<int, list<int>>
     */
    private function ids(array $groups): array
    {
        return array_map(fn (array $group): array => array_map(fn (object $row): int => $row->id, $group), $groups);
    }
}
