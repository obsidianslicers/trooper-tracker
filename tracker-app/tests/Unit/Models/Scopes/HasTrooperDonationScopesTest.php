<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Models\TrooperDonation;
use Carbon\Carbon;
use Tests\TestCase;

class HasTrooperDonationScopesTest extends TestCase
{
    public function test_by_trooper_adds_filter_and_descending_created_at_order(): void
    {
        $query = TrooperDonation::query()->byTrooper(99);

        $this->assertStringContainsString('"trooper_id" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('order by "created_at" desc', $query->toBase()->toSql());
        $this->assertSame([99], $query->getBindings());
    }

    public function test_for_month_adds_between_constraint(): void
    {
        $query = TrooperDonation::query()->forMonth(Carbon::parse('2026-02-10 12:00:00'));

        $this->assertStringContainsString('"created_at" between ? and ?', $query->toBase()->toSql());
        $this->assertCount(2, $query->getBindings());
    }
}