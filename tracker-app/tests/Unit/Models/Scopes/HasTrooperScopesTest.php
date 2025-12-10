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

    public function test_by_username_scope(): void
    {
        Trooper::factory()->create(['username' => 'jdoe']);
        Trooper::factory()->create(['username' => 'jane.d']);

        $result = Trooper::byUsername('jdoe')->first();

        $this->assertNotNull($result);
        $this->assertEquals('jdoe', $result->username);
        $this->assertEquals(1, Trooper::byUsername('jdoe')->count());
    }

    public function test_pending_approvals_scope(): void
    {
        Trooper::factory()->asPending()->create(['name' => 'Alpha']);
        Trooper::factory()->asActive()->create(['name' => 'Beta']);
        Trooper::factory()->asPending()->create(['name' => 'Gamma']);

        $results = Trooper::pendingApprovals()->get();

        $this->assertCount(2, $results);
        $this->assertEquals('Alpha', $results[0]->name);
        $this->assertEquals('Gamma', $results[1]->name);
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
        Trooper::factory()->create(['name' => 'John Smith', 'username' => 'jsmith', 'email' => 'jsmith@test.com']);
        Trooper::factory()->create(['name' => 'Jane Doe', 'username' => 'jdoe', 'email' => 'jane.doe@test.com']);
        Trooper::factory()->create(['name' => 'Peter Jones', 'username' => 'pete', 'email' => 'pete.j@test.com']);

        // Search by part of name
        $results = Trooper::searchFor('smi')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('jsmith', $results[0]->username);

        // Search by part of username
        $results = Trooper::searchFor('jdo')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('jdoe', $results[0]->username);

        // Search by part of email
        $results = Trooper::searchFor('pete.j')->get();
        $this->assertCount(1, $results);
        $this->assertEquals('pete', $results[0]->username);

        // Search term that matches multiple records
        $results = Trooper::searchFor('j')->get();
        $this->assertCount(3, $results);

        // Search term that matches nothing
        $results = Trooper::searchFor('xyz')->get();
        $this->assertCount(0, $results);
    }

    public function test_search_for_scope_handles_wildcards(): void
    {
        Trooper::factory()->create(['name' => 'Test User', 'username' => 'testuser', 'email' => 'test@test.com']);

        // Test with no wildcards
        $query = Trooper::searchFor('estus');
        $this->assertStringContainsString('where ("email" like ? or "username" like ? or "name" like ?)', $query->toSql());
        $this->assertEquals(['%estus%', '%estus%', '%estus%'], $query->getBindings());

        // Test with leading wildcard
        $query = Trooper::searchFor('%estus');
        $this->assertEquals(['%estus%', '%estus%', '%estus%'], $query->getBindings());

        // Test with trailing wildcard
        $query = Trooper::searchFor('estus%');
        $this->assertEquals(['%estus%', '%estus%', '%estus%'], $query->getBindings());

        // Test with both wildcards
        $query = Trooper::searchFor('%estus%');
        $this->assertEquals(['%estus%', '%estus%', '%estus%'], $query->getBindings());
    }
}
