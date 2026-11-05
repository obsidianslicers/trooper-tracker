<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Concerns;

use App\Messages\Concerns\HasTrooperOrgCreditQuery;
use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HasTrooperOrgCreditQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_apply_adds_nothing_without_any_scope(): void
    {
        $query = $this->eventTroopers();

        $this->subject()->apply($query, [], []);

        $this->assertSame([], $query->wheres);
    }

    public function test_apply_with_roster_orgs_checks_membership_and_direct_credit(): void
    {
        $query = $this->eventTroopers();

        $this->subject()->apply($query, [7], []);

        $sql = $query->toSql();
        $this->assertStringContainsString('"ta_credit"."is_member" = ?', $sql);
        $this->assertStringContainsString('"trooper_org"."id" in (?)', $sql);
        $this->assertStringContainsString('"tt_event_troopers"."organization_id" in (?)', $sql);
        $this->assertStringNotContainsString('SUBSTRING_INDEX', $sql);
    }

    public function test_apply_with_accessible_roots_scopes_by_root_and_its_subtree(): void
    {
        $root = Organization::factory()->asOrganization()->create();
        $child = Organization::factory()->asRegion()->withParent($root)->create();
        $query = $this->eventTroopers();

        $this->subject()->apply($query, [], [$root->id]);

        $this->assertStringContainsString('SUBSTRING_INDEX', $query->toSql());
        $this->assertContains($child->id, $query->getBindings());
    }

    public function test_direct_credit_matches_costume_credit_or_signup_organization(): void
    {
        $credited = Organization::factory()->create();
        $other = Organization::factory()->create();

        $by_costume = EventTrooper::factory()->withCostumeOrganizationIds([$credited->id])
            ->state([EventTrooper::ORGANIZATION_ID => $other->id])->create();
        $by_signup = EventTrooper::factory()->withCostumeOrganizationIds([])
            ->state([EventTrooper::ORGANIZATION_ID => $credited->id])->create();
        EventTrooper::factory()->withCostumeOrganizationIds([$other->id])
            ->state([EventTrooper::ORGANIZATION_ID => $credited->id])->create();

        $query = $this->eventTroopers();
        $this->subject()->direct($query, [$credited->id]);

        $this->assertEqualsCanonicalizing(
            [$by_costume->id, $by_signup->id],
            $query->pluck('tt_event_troopers.id')->all()
        );
    }

    public function test_direct_credit_with_empty_scope_matches_nothing(): void
    {
        EventTrooper::factory()->create();

        $query = $this->eventTroopers();
        $this->subject()->direct($query, []);

        $this->assertSame(0, $query->count());
    }

    public function test_resolve_org_subtree_ids_includes_descendants(): void
    {
        $root = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($root)->create();
        $unit = Organization::factory()->asUnit()->withParent($region)->create();
        Organization::factory()->asOrganization()->create();

        $this->assertEqualsCanonicalizing(
            [$region->id, $unit->id],
            $this->subject()->orgSubtree($region->fresh())
        );
        $this->assertEqualsCanonicalizing(
            [$root->id, $region->id, $unit->id],
            $this->subject()->rootSubtree([$root->id])
        );
    }

    public function test_resolve_subtrees_return_empty_for_no_input(): void
    {
        $this->assertSame([], $this->subject()->orgSubtree(null));
        $this->assertSame([], $this->subject()->rootSubtree([]));
    }

    private function eventTroopers(): Builder
    {
        return DB::table('tt_event_troopers');
    }

    private function subject(): object
    {
        return new class {
            use HasTrooperOrgCreditQuery;

            public function apply(Builder $q, array $roster, array $roots): void
            {
                $this->applyTrooperOrgCredit($q, $roster, $roots);
            }

            public function direct(Builder $q, array $scope): void
            {
                $this->whereDirectCredit($q, $scope);
            }

            public function orgSubtree(?Organization $organization): array
            {
                return $this->resolveOrgSubtreeIds($organization);
            }

            public function rootSubtree(array $root_ids): array
            {
                return $this->resolveRootSubtreeIds($root_ids);
            }
        };
    }
}
