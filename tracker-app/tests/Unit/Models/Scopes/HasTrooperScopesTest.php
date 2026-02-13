<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasTrooperScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_approvals_scope(): void
    {
        Trooper::factory()->asPending()->create([Trooper::DISPLAY_NAME => 'Alpha']);
        Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Beta']);
        Trooper::factory()->asPending()->create([Trooper::DISPLAY_NAME => 'Gamma']);

        $results = Trooper::pendingApprovals()->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Alpha', $results[0]->display_name);
        $this->assertEquals('Gamma', $results[1]->display_name);
    }

    public function test_moderated_by_scope_for_administrator(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();

        /** @var Builder $query */
        $query = Trooper::query();
        $initial_sql = $query->toSql();

        $result_query = $query->moderatedBy($admin);

        // For an admin, the query should not be modified.
        $this->assertEquals($initial_sql, $result_query->toSql());
    }

    public function test_moderated_by_scope_for_moderator(): void
    {
        $moderator = Trooper::factory()->create();

        /** @var Builder $query */
        $query = Trooper::query();
        $result_query = $query->moderatedBy($moderator);

        $sql = $result_query->toSql();
        $bindings = $result_query->getBindings();

        $this->assertStringContainsString('where exists (select 1 from "tt_trooper_assignments" as "ta_moderator"', $sql);
        $this->assertStringContainsString('"ta_moderator"."trooper_id" = ?', $sql);
        $this->assertStringContainsString('"ta_moderator"."is_moderator" = ?', $sql);
        $this->assertStringContainsString('org_candidate.node_path LIKE CONCAT(org_moderator.node_path, "%")', $sql);
        $this->assertEquals([$moderator->id, 1], $bindings);
    }

    public function test_search_for_scope(): void
    {
        Trooper::factory()->create([Trooper::DISPLAY_NAME => 'John Smith', Trooper::EMAIL => 'jsmith@test.com']);
        Trooper::factory()->create([Trooper::DISPLAY_NAME => 'Jane Doe', Trooper::EMAIL => 'jane.doe@test.com']);
        Trooper::factory()->create([Trooper::DISPLAY_NAME => 'Peter Jones', Trooper::EMAIL => 'pete.j@test.com']);

        // Search by part of name
        $results = Trooper::searchFor('smi')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('John Smith', $results[0]->display_name);

        // Search by part of email
        $results = Trooper::searchFor('pete.j')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Peter Jones', $results[0]->display_name);

        // Search term that matches multiple records
        $results = Trooper::searchFor('j')->get();
        $this->assertCount(3, $results);

        // Search term that matches nothing
        $results = Trooper::searchFor('xyz')->get();
        $this->assertCount(0, $results);
    }

    public function test_search_for_scope_handles_wildcards(): void
    {
        Trooper::factory()->create([Trooper::DISPLAY_NAME => 'Test User', Trooper::EMAIL => 'test@test.com']);

        // Test with no wildcards
        $query = Trooper::searchFor('estus');
        $this->assertStringContainsString('where ("email" like ? or "display_name" like ?)', $query->toSql());
        $this->assertEquals(['%estus%', '%estus%'], $query->getBindings());

        // Test with leading wildcard
        $query = Trooper::searchFor('%estus');
        $this->assertEquals(['%estus%', '%estus%'], $query->getBindings());

        // Test with trailing wildcard
        $query = Trooper::searchFor('estus%');
        $this->assertEquals(['%estus%', '%estus%'], $query->getBindings());
        // Test with both wildcards
        $query = Trooper::searchFor('%estus%');
        $this->assertEquals(['%estus%', '%estus%'], $query->getBindings());
    }

    public function test_active_scope_returns_only_active_troopers(): void
    {
        // Arrange
        Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Active Trooper']);
        Trooper::factory()->asPending()->create([Trooper::DISPLAY_NAME => 'Pending Trooper']);
        Trooper::factory()->asRetired()->create([Trooper::DISPLAY_NAME => 'Retired Trooper']);

        // Act
        $results = Trooper::active()->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Active Trooper', $results[0]->display_name);
        $this->assertEquals(MembershipStatus::ACTIVE, $results[0]->membership_status);
    }

    public function test_active_scope_filters_correctly(): void
    {
        // Arrange
        $active_trooper = Trooper::factory()->asActive()->create();
        $pending_trooper = Trooper::factory()->asPending()->create();

        // Act
        $active_results = Trooper::active()->pluck('id')->toArray();

        // Assert
        $this->assertContains($active_trooper->id, $active_results);
        $this->assertNotContains($pending_trooper->id, $active_results);
    }

    public function test_active_scope_returns_empty_when_no_active_troopers(): void
    {
        // Arrange
        Trooper::factory()->asPending()->create();
        Trooper::factory()->asRetired()->create();

        // Act
        $results = Trooper::active()->get();

        // Assert
        $this->assertCount(0, $results);
    }
}
