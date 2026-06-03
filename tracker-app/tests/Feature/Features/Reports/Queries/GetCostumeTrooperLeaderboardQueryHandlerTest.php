<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetCostumeTrooperLeaderboardQuery;
use App\Features\Reports\Queries\GetCostumeTrooperLeaderboardQueryHandler;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class GetCostumeTrooperLeaderboardQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_array_with_top_troopers_and_stats_keys(): void
    {
        $costume = Costume::factory()->create();

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('top_troopers', $result);
        $this->assertArrayHasKey('stats', $result);
        $this->assertInstanceOf(Collection::class, $result['top_troopers']);
        $this->assertIsArray($result['stats']);
    }

    public function test_invoke_top_troopers_are_ordered_by_troop_count_descending(): void
    {
        $costume = Costume::factory()->create();
        $top_trooper = Trooper::factory()->asMember()->create();
        $low_trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($top_trooper, $costume, now()->subDays(3));
        $this->createAttendance($top_trooper, $costume, now()->subDays(4));
        $this->createAttendance($top_trooper, $costume, now()->subDays(5));
        $this->createAttendance($low_trooper, $costume, now()->subDays(6));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertSame($top_trooper->id, $result['top_troopers']->first()->trooper_id);
        $this->assertSame(3, (int) $result['top_troopers']->first()->troop_count);
    }

    public function test_invoke_only_counts_costume_matching_the_query(): void
    {
        $costume = Costume::factory()->create();
        $other_costume = Costume::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($trooper, $costume, now()->subDays(3));
        $this->createAttendance($trooper, $other_costume, now()->subDays(4));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertSame(1, (int) $result['top_troopers']->first()->troop_count);
        $this->assertSame(1, $result['stats']['total_deployments']);
    }

    public function test_invoke_only_counts_attended_status(): void
    {
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($trooper, $costume, now()->subDays(3));

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(4))->create();
        $shift = EventShift::factory()->forEvent($event)->create();
        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asGoing()
            ->create();

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertSame(1, (int) $result['top_troopers']->first()->troop_count);
    }

    public function test_invoke_only_counts_closed_events(): void
    {
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($trooper, $costume, now()->subDays(3));
        $this->createAttendance($trooper, $costume, now()->subDays(4), null, false);

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertSame(1, (int) $result['top_troopers']->first()->troop_count);
    }

    public function test_invoke_excludes_inactive_troopers(): void
    {
        $costume = Costume::factory()->create();
        $active_trooper = Trooper::factory()->asMember()->create();
        $retired_trooper = Trooper::factory()->asRetired()->create();

        $this->createAttendance($active_trooper, $costume, now()->subDays(3));
        $this->createAttendance($retired_trooper, $costume, now()->subDays(4));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $trooper_ids = $result['top_troopers']->pluck('trooper_id');

        $this->assertTrue($trooper_ids->contains($active_trooper->id));
        $this->assertFalse($trooper_ids->contains($retired_trooper->id));
    }

    public function test_invoke_excludes_placeholder_accounts(): void
    {
        $costume = Costume::factory()->create();
        $placeholder = Trooper::factory()->asMember()->withDisplayName('Placeholder')->create();

        $this->createAttendance($placeholder, $costume, now()->subDays(3));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertTrue($result['top_troopers']->isEmpty());
    }

    public function test_invoke_respects_lookback_days_filter(): void
    {
        $costume = Costume::factory()->create();
        $recent_trooper = Trooper::factory()->asMember()->create();
        $old_trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($recent_trooper, $costume, now()->subDays(5));
        $this->createAttendance($old_trooper, $costume, now()->subDays(90));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume, 30));

        $trooper_ids = $result['top_troopers']->pluck('trooper_id');

        $this->assertTrue($trooper_ids->contains($recent_trooper->id));
        $this->assertFalse($trooper_ids->contains($old_trooper->id));
    }

    public function test_invoke_respects_limit(): void
    {
        $costume = Costume::factory()->create();
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
                ->withCostume($costume)
                ->asAttended()
                ->create();
        }

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume, null, null, 30));

        $this->assertCount(30, $result['top_troopers']);
    }

    public function test_invoke_stats_total_deployments_is_correct(): void
    {
        $costume = Costume::factory()->create();
        $trooper_a = Trooper::factory()->asMember()->create();
        $trooper_b = Trooper::factory()->asMember()->create();

        $this->createAttendance($trooper_a, $costume, now()->subDays(3));
        $this->createAttendance($trooper_a, $costume, now()->subDays(4));
        $this->createAttendance($trooper_b, $costume, now()->subDays(5));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertSame(3, $result['stats']['total_deployments']);
    }

    public function test_invoke_stats_unique_troopers_is_correct(): void
    {
        $costume = Costume::factory()->create();
        $trooper_a = Trooper::factory()->asMember()->create();
        $trooper_b = Trooper::factory()->asMember()->create();

        $this->createAttendance($trooper_a, $costume, now()->subDays(3));
        $this->createAttendance($trooper_a, $costume, now()->subDays(4));
        $this->createAttendance($trooper_b, $costume, now()->subDays(5));

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertSame(2, $result['stats']['unique_troopers']);
    }

    public function test_invoke_stats_last_deployed_at_returns_most_recent_event_start(): void
    {
        $costume = Costume::factory()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $older = now()->subDays(10)->startOfDay();
        $newer = now()->subDays(2)->startOfDay();

        $this->createAttendance($trooper, $costume, $older);
        $this->createAttendance($trooper, $costume, $newer);

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertNotNull($result['stats']['last_deployed_at']);
        $this->assertTrue($result['stats']['last_deployed_at']->isSameDay($newer));
    }

    public function test_invoke_stats_last_deployed_at_is_null_when_no_attendance(): void
    {
        $costume = Costume::factory()->create();

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume));

        $this->assertNull($result['stats']['last_deployed_at']);
    }

    public function test_invoke_org_filter_excludes_other_org_attendance(): void
    {
        $costume = Costume::factory()->create();
        $org = Organization::factory()->asOrganization()->create();
        $other_org = Organization::factory()->asOrganization()->create();
        $my_trooper = Trooper::factory()->asMember()->create();
        $other_trooper = Trooper::factory()->asMember()->create();

        $this->createAttendance($my_trooper, $costume, now()->subDays(3), $org);
        $this->createAttendance($other_trooper, $costume, now()->subDays(4), $other_org);

        $subject = new GetCostumeTrooperLeaderboardQueryHandler;
        $result = $subject(new GetCostumeTrooperLeaderboardQuery($costume, null, $org));

        $trooper_ids = $result['top_troopers']->pluck('trooper_id');

        $this->assertTrue($trooper_ids->contains($my_trooper->id));
        $this->assertFalse($trooper_ids->contains($other_trooper->id));
    }

    private function createAttendance(
        Trooper $trooper,
        Costume $costume,
        Carbon $event_start,
        ?Organization $organization = null,
        bool $closed = true,
    ): EventTrooper {
        $event_factory = Event::factory()->withEventStart($event_start);

        if ($closed)
        {
            $event_factory = $event_factory->asClosed();
        }

        $event = $event_factory->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        return EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper)
            ->withCostume($costume)
            ->asAttended()
            ->create([
                EventTrooper::ORGANIZATION_ID => $organization?->id,
            ]);
    }
}
