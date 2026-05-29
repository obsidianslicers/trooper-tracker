<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetLeaderboardMetricsQuery;
use App\Features\Reports\Queries\GetLeaderboardMetricsQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GetLeaderboardMetricsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_leaderboard_array_with_expected_sections(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler;

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('dominance', $result);
        $this->assertArrayHasKey('diversity', $result);
        $this->assertArrayHasKey('operatives', $result);
    }

    public function test_invoke_dominance_section_returns_collection(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler;

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertInstanceOf(Collection::class, $result['dominance']);
    }

    public function test_invoke_diversity_section_returns_collection(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler;

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertInstanceOf(Collection::class, $result['diversity']);
    }

    public function test_invoke_operatives_section_returns_collection(): void
    {
        $subject = new GetLeaderboardMetricsQueryHandler;

        $result = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertInstanceOf(Collection::class, $result['operatives']);
    }

    public function test_invoke_all_time_includes_attendance_outside_30_day_window(): void
    {
        $old_trooper = Trooper::factory()->asMember()->create();
        $recent_trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($old_trooper, now()->subDays(90));
        $this->createAttendance($recent_trooper, now()->subDays(5));

        $subject = new GetLeaderboardMetricsQueryHandler;

        $all_time = $subject(new GetLeaderboardMetricsQuery);
        $thirty_days = $subject(new GetLeaderboardMetricsQuery(30));

        $this->assertTrue($all_time['operatives']->pluck('trooper_id')->contains($old_trooper->id));
        $this->assertFalse(
            $thirty_days['operatives']->pluck('trooper_id')->contains($old_trooper->id)
        );
        $this->assertTrue(
            $thirty_days['operatives']->pluck('trooper_id')->contains($recent_trooper->id)
        );
    }

    public function test_invoke_operatives_are_limited_to_30(): void
    {
        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(5))->create();

        foreach (range(1, 31) as $index)
        {
            $trooper = Trooper::factory()->asMember()->create();
            $shift = EventShift::factory()
                ->forEvent($event)
                ->withShiftStartsAt(now()->addMinutes($index))
                ->create();

            EventTrooper::factory()
                ->forEventShift($shift)
                ->forTrooper($trooper)
                ->asAttended()
                ->create();
        }

        $subject = new GetLeaderboardMetricsQueryHandler;
        $result = $subject(new GetLeaderboardMetricsQuery(null, null, 30));

        $this->assertCount(30, $result['operatives']);
    }

    public function test_invoke_club_filter_includes_direct_and_costume_credit(): void
    {
        $club = Organization::factory()->asOrganization()->create();
        $other_club = Organization::factory()->asOrganization()->create();
        $direct_trooper = Trooper::factory()->asMember()->create();
        $costume_credit_trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($direct_trooper, now()->subDays(5), $club);
        $this->createAttendance($costume_credit_trooper, now()->subDays(5), null, [$club->id]);
        $this->createAttendance($other_trooper, now()->subDays(5), $other_club);

        $subject = new GetLeaderboardMetricsQueryHandler;
        $result = $subject(new GetLeaderboardMetricsQuery(null, $club));
        $trooper_ids = $result['operatives']->pluck('trooper_id');

        $this->assertTrue($trooper_ids->contains($direct_trooper->id));
        $this->assertTrue($trooper_ids->contains($costume_credit_trooper->id));
        $this->assertFalse($trooper_ids->contains($other_trooper->id));
    }

    public function test_invoke_operatives_count_only_attended_records_on_closed_events(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($trooper, now()->subDays(5));
        $this->createAttendance($trooper, now()->subDays(4), null, [], false);

        $closed_event = Event::factory()->asClosed()->withEventStart(now()->subDays(3))->create();
        $shift = EventShift::factory()->forEvent($closed_event)->create();
        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asGoing()
            ->create();

        $subject = new GetLeaderboardMetricsQueryHandler;
        $result = $subject(new GetLeaderboardMetricsQuery);

        $this->assertSame(1, (int) $result['operatives']->first()->troop_count);
    }

    /**
     * @param  array<int, int>  $costume_organization_ids
     */
    private function createAttendance(
        Trooper $trooper,
        Carbon $event_start,
        ?Organization $organization = null,
        array $costume_organization_ids = [],
        bool $closed = true,
    ): EventTrooper {
        $event_factory = Event::factory()->withEventStart($event_start);

        if ($closed)
        {
            $event_factory = $event_factory->asClosed();
        }

        $event = $event_factory->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        $event_trooper = EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->asAttended()
            ->create([
                EventTrooper::ORGANIZATION_ID => $organization?->id,
                EventTrooper::COSTUME_ORGANIZATION_IDS => $costume_organization_ids ?: null,
            ]);

        if ($costume_organization_ids !== [])
        {
            DB::table('tt_event_troopers')
                ->where(EventTrooper::ID, $event_trooper->id)
                ->update([
                    EventTrooper::ORGANIZATION_ID => null,
                    EventTrooper::COSTUME_ORGANIZATION_IDS => json_encode(
                        $costume_organization_ids
                    ),
                ]);

            $event_trooper->refresh();
        }

        return $event_trooper;
    }
}
