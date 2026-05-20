<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Tests\TestCase;

class HasTrooperScopesTest extends TestCase
{
    public function test_by_email_adds_email_filter(): void
    {
        $query = Trooper::query()->byEmail('tk421@example.com');

        $this->assertStringContainsString('"email" = ?', $query->toBase()->toSql());
        $this->assertSame(['tk421@example.com'], $query->getBindings());
    }

    public function test_active_filters_to_active_membership_status(): void
    {
        $query = Trooper::query()->active();

        $this->assertStringContainsString('"membership_status" = ?', $query->toBase()->toSql());
        $this->assertSame([MembershipStatus::ACTIVE->value], $query->getBindings());
    }

    public function test_search_for_wraps_term_with_wildcards(): void
    {
        $query = Trooper::query()->searchFor('vader');

        $this->assertStringContainsString('"email" like ?', $query->toBase()->toSql());
        $this->assertStringContainsString('or "display_name" like ?', $query->toBase()->toSql());
        $this->assertStringContainsString('or "legal_name" like ?', $query->toBase()->toSql());
        $this->assertSame(['%vader%', '%vader%', '%vader%'], $query->getBindings());
    }

    public function test_pending_approvals_filters_and_orders(): void
    {
        $query = Trooper::query()->pendingApprovals();

        $this->assertStringContainsString('"membership_status" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('order by "display_name" asc', $query->toBase()->toSql());
        $this->assertSame([MembershipStatus::PENDING->value], $query->getBindings());
    }
}