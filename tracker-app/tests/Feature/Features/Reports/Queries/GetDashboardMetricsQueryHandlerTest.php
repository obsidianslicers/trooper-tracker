<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Reports\Queries;

use App\Features\Reports\Queries\GetDashboardMetricsQuery;
use App\Features\Reports\Queries\GetDashboardMetricsQueryHandler;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetDashboardMetricsQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_metrics_array_with_expected_sections(): void
    {
        $subject = new GetDashboardMetricsQueryHandler();

        $result = $subject(new GetDashboardMetricsQuery(30));

        $this->assertIsArray($result);
        $this->assertArrayHasKey('personnel', $result);
        $this->assertArrayHasKey('logistics', $result);
        $this->assertArrayHasKey('impact', $result);
        $this->assertArrayHasKey('engagement', $result);
        $this->assertArrayHasKey('leaderboard', $result);
    }

    public function test_invoke_personnel_section_counts_active_troopers(): void
    {
        Trooper::factory()->asMember()->count(3)->create();
        Trooper::factory()->asPending()->count(2)->create();

        $subject = new GetDashboardMetricsQueryHandler();

        $result = $subject(new GetDashboardMetricsQuery(30));

        $this->assertArrayHasKey('active_count', $result['personnel']);
        $this->assertSame(3, $result['personnel']['active_count']);
    }

    public function test_invoke_personnel_section_counts_new_enlistments(): void
    {
        Trooper::factory()->asMember()->create(['created_at' => now()->subDays(5)]);
        Trooper::factory()->asMember()->create(['created_at' => now()->subDays(40)]);

        $subject = new GetDashboardMetricsQueryHandler();

        $result = $subject(new GetDashboardMetricsQuery(30));

        $this->assertArrayHasKey('new_enlistments', $result['personnel']);
        $this->assertSame(1, $result['personnel']['new_enlistments']);
    }

    public function test_invoke_personnel_section_counts_attendance(): void
    {
        $trooper_with_attendance = Trooper::factory()->asMember()->create();
        $trooper_without_attendance = Trooper::factory()->asMember()->create();

        $event = Event::factory()->asClosed()->withEventStart(now()->subDays(10))->create();
        $shift = EventShift::factory()->forEvent($event)->create();

        EventTrooper::factory()
            ->forEventShift($shift)
            ->forTrooper($trooper_with_attendance)
            ->asAttended()
            ->withSignedUpAt(now()->subDays(10))
            ->create();

        $subject = new GetDashboardMetricsQueryHandler();

        $result = $subject(new GetDashboardMetricsQuery(30));

        $this->assertArrayHasKey('attendance_count', $result['personnel']);
        $this->assertSame(1, $result['personnel']['attendance_count']);
    }

    public function test_invoke_respects_lookback_period(): void
    {
        Trooper::factory()->asMember()->create(['created_at' => Carbon::parse('2026-01-15')]);
        Trooper::factory()->asMember()->create(['created_at' => Carbon::parse('2026-02-15')]);

        $subject = new GetDashboardMetricsQueryHandler();

        $result = $subject(new GetDashboardMetricsQuery(Carbon::parse('2026-02-01')));

        $this->assertSame(1, $result['personnel']['new_enlistments']);
    }
}
