<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Filters;

use App\Models\Filters\NoticeFilter;
use App\Models\Notice;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class NoticeFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_filter_by_active_scope(): void
    {
        $active_notice = Notice::factory()->create(['starts_at' => Carbon::yesterday(), 'ends_at' => Carbon::tomorrow()]);
        Notice::factory()->create(['starts_at' => Carbon::yesterday()->subDays(2), 'ends_at' => Carbon::yesterday()]);

        $request = new Request(['scope' => 'active']);
        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($active_notice->id, $query->first()->id);
    }

    public function test_it_can_filter_by_past_scope(): void
    {
        Notice::factory()->create(['starts_at' => Carbon::yesterday(), 'ends_at' => Carbon::tomorrow()]);
        $past_notice = Notice::factory()->create(['starts_at' => Carbon::yesterday()->subDays(2), 'ends_at' => Carbon::yesterday()]);

        $request = new Request(['scope' => 'past']);
        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($past_notice->id, $query->first()->id);
    }

    public function test_it_can_filter_by_future_scope(): void
    {
        Notice::factory()->create(['starts_at' => Carbon::yesterday(), 'ends_at' => Carbon::tomorrow()]);
        $future_notice = Notice::factory()->create(['starts_at' => Carbon::tomorrow(), 'ends_at' => Carbon::tomorrow()->addDay()]);

        $request = new Request(['scope' => 'future']);
        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($future_notice->id, $query->first()->id);
    }

    public function test_it_defaults_to_active_scope_with_invalid_value(): void
    {
        $active_notice = Notice::factory()->create(['starts_at' => Carbon::yesterday(), 'ends_at' => Carbon::tomorrow()]);
        Notice::factory()->create(['starts_at' => Carbon::tomorrow(), 'ends_at' => Carbon::tomorrow()->addDay()]);

        $request = new Request(['scope' => 'invalid-scope']);
        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($active_notice->id, $query->first()->id);
    }

    public function test_it_can_filter_by_organization(): void
    {
        $organization_a = Organization::factory()->create();
        $organization_b = Organization::factory()->create();
        $notice_for_org_a = Notice::factory()->create([Notice::ORGANIZATION_ID => $organization_a->id]);
        Notice::factory()->create([Notice::ORGANIZATION_ID => $organization_b->id]);

        $request = new Request(['organization_id' => $organization_a->id]);
        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($notice_for_org_a->id, $query->first()->id);
    }

    public function test_it_can_apply_multiple_filters(): void
    {
        $organization = Organization::factory()->create();

        // The notice we expect to find
        $matching_notice = Notice::factory()->create([
            Notice::ORGANIZATION_ID => $organization->id,
            'starts_at' => Carbon::yesterday(),
            'ends_at' => Carbon::tomorrow(),
        ]);

        // Decoys
        Notice::factory()->create([Notice::ORGANIZATION_ID => $organization->id, 'starts_at' => Carbon::tomorrow()]);
        Notice::factory()->create(['starts_at' => Carbon::yesterday(), 'ends_at' => Carbon::tomorrow()]);

        $request = new Request(['organization_id' => $organization->id, 'scope' => 'active']);
        $subject = new NoticeFilter($request);

        $query = $subject->apply(Notice::query());

        $this->assertEquals(1, $query->count());
        $this->assertEquals($matching_notice->id, $query->first()->id);
    }
}