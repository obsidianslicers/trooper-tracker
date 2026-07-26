<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasTrooperScopesTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertStringContainsString('or ("display_name" like ?)', $query->toBase()->toSql());
        $this->assertStringContainsString('or ("legal_name" like ?)', $query->toBase()->toSql());
        $this->assertStringContainsString('or exists', $query->toBase()->toSql());
        $this->assertSame(['%vader%', '%vader%', '%vader%', '%vader%'], $query->getBindings());
    }

    public function test_search_for_matches_any_token_order_in_same_field(): void
    {
        Trooper::factory()->withDisplayName('Matthew Drennan')->create();

        $this->assertTrue(Trooper::query()->searchFor('drennan matthew')->exists());
        $this->assertTrue(Trooper::query()->searchFor('matthew drennan')->exists());
        $this->assertFalse(Trooper::query()->searchFor('matthew smith')->exists());
    }

    public function test_search_for_any_matches_when_only_one_token_is_present(): void
    {
        Trooper::factory()->withDisplayName('Matthew Drennan')->create();

        $this->assertTrue(Trooper::query()->searchForAny('matthew smith')->exists());
        $this->assertFalse(Trooper::query()->searchForAny('luke smith')->exists());
    }

    public function test_search_for_matches_organization_identifier(): void
    {
        $trooper = Trooper::factory()->withDisplayName('Identifier Match')->create();
        $organization = Organization::factory()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-421')
            ->create();

        $this->assertTrue(Trooper::query()->searchFor('TK-421')->exists());
        $this->assertTrue(Trooper::query()->searchForAny('TK-421')->exists());
        $this->assertFalse(Trooper::query()->searchFor('TK-999')->exists());
    }

    public function test_search_for_ignores_soft_deleted_organization_identifier(): void
    {
        $trooper = Trooper::factory()->withDisplayName('Deleted Identifier')->create();
        $organization = Organization::factory()->create();

        $membership = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-421')
            ->create();
        $membership->delete();

        $this->assertFalse(Trooper::query()->searchFor('TK-421')->exists());
    }

    public function test_order_by_relevance_ranks_starts_with_before_contains(): void
    {
        Trooper::factory()->withDisplayName('Anakin Skywalker')->create();
        Trooper::factory()->withDisplayName('Skywalker Ranch')->create();

        $result = Trooper::query()->orderByRelevance('Skywalker')->get();

        $this->assertSame(
            ['Skywalker Ranch', 'Anakin Skywalker'],
            $result->pluck(Trooper::DISPLAY_NAME)->all()
        );
    }

    public function test_order_by_relevance_ranks_display_name_before_legal_name(): void
    {
        Trooper::factory()->withDisplayName('Callsign Only')->withLegalName('Skywalker Legal')->create();
        Trooper::factory()->withDisplayName('Skywalker Display')->create();

        $result = Trooper::query()->orderByRelevance('Skywalker')->get();

        $this->assertSame(
            ['Skywalker Display', 'Callsign Only'],
            $result->pluck(Trooper::DISPLAY_NAME)->all()
        );
    }

    public function test_pending_approvals_filters_and_orders(): void
    {
        $query = Trooper::query()->pendingApprovals();

        $this->assertStringContainsString('"membership_status" = ?', $query->toBase()->toSql());
        $this->assertStringContainsString('order by "display_name" asc', $query->toBase()->toSql());
        $this->assertSame([MembershipStatus::PENDING->value], $query->getBindings());
    }
}
