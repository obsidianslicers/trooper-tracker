<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\Notice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasNoticeScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_scope_adds_current_date_window_constraints(): void
    {
        $query = Notice::query()->active();

        $this->assertStringContainsString('"starts_at" <= ?', $query->toBase()->toSql());
        $this->assertStringContainsString('"ends_at" >= ?', $query->toBase()->toSql());
    }

    public function test_past_scope_adds_past_date_constraints(): void
    {
        $query = Notice::query()->past();

        $this->assertStringContainsString('"starts_at" < ?', $query->toBase()->toSql());
        $this->assertStringContainsString('"ends_at" < ?', $query->toBase()->toSql());
    }

    public function test_future_scope_adds_future_date_constraints(): void
    {
        $query = Notice::query()->future();

        $this->assertStringContainsString('"starts_at" > ?', $query->toBase()->toSql());
        $this->assertStringContainsString('"ends_at" > ?', $query->toBase()->toSql());
    }

    public function test_moderated_by_returns_unmodified_query_for_administrator(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $base_sql = Notice::query()->toBase()->toSql();
        $moderated_sql = Notice::query()->moderatedBy($trooper)->toBase()->toSql();

        $this->assertSame($base_sql, $moderated_sql);
    }
}