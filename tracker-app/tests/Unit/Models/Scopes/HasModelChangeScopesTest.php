<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\ModelChange;
use Carbon\Carbon;
use Tests\TestCase;

class HasModelChangeScopesTest extends TestCase
{
    public function test_recent_adds_created_at_lookback_filter(): void
    {
        $query = ModelChange::query()->recent(Carbon::parse('2026-03-01 00:00:00'));

        $this->assertStringContainsString('"created_at" >= ?', $query->toBase()->toSql());
        $this->assertCount(1, $query->getBindings());
    }
}