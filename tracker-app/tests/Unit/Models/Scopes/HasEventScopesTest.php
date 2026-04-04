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

        $this->assertStringContainsString('"status" in (?, ?, ?, ?)', $query->toBase()->toSql());
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

        $this->assertStringContainsString('"event_start" >= ?', $query->toBase()->toSql());
        $this->assertStringContainsString('order by "event_start" asc', $query->toBase()->toSql());
    }

    public function test_search_for_wraps_term_with_wildcards(): void
    {
        $query = Event::query()->searchFor('charity');

        $this->assertStringContainsString('"name" like ?', $query->toBase()->toSql());
        $this->assertSame(['%charity%'], $query->getBindings());
    }

    public function test_moderated_by_returns_unmodified_query_for_administrator(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $base_sql = Event::query()->toBase()->toSql();
        $moderated_sql = Event::query()->moderatedBy($trooper)->toBase()->toSql();

        $this->assertSame($base_sql, $moderated_sql);
    }
}