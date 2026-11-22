<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\EventStatus;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasEventScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_filters_to_open_manual_selection_draft_and_locked_statuses(): void
    {
        $query = Event::query()->active();

        $this->assertStringContainsString('"status" in (?, ?, ?, ?)', $this->sql($query));
        $this->assertSame([
            EventStatus::OPEN->value,
            EventStatus::MANUAL_SELECTION->value,
            EventStatus::DRAFT->value,
            EventStatus::SIGN_UP_LOCKED->value,
        ], $query->getBindings());
    }

    public function test_upcoming_scope_adds_date_and_sorting_constraints(): void
    {
        $query = Event::query()->upcoming();

        $this->assertStringContainsString('"shift_ends_at" >= ?', $this->sql($query));
        $this->assertStringContainsString('"event_end" >= ?', $this->sql($query));
        $this->assertStringContainsString('order by "event_start" asc', $this->sql($query));
    }

    public function test_search_for_wraps_term_with_wildcards(): void
    {
        $query = Event::query()->searchFor('charity');

        $this->assertStringContainsString('"name" like ?', $this->sql($query));
        $this->assertSame(['%charity%'], $query->getBindings());
    }

    public function test_moderated_by_returns_unmodified_query_for_administrator(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $base_sql = $this->sql(Event::query());
        $moderated_sql = $this->sql(Event::query()->moderatedBy($trooper));

        $this->assertSame($base_sql, $moderated_sql);
    }
}
