<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Policies\OrganizationPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_requires_administrator(): void
    {
        $policy = new OrganizationPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();

        $this->assertTrue($policy->create($administrator));
        $this->assertFalse($policy->create($member));
    }

    public function test_update_and_moderate_allow_administrator_for_any_organization(): void
    {
        $policy = new OrganizationPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->withNodePath('org.any')->create();

        $this->assertTrue($policy->update($administrator, $organization));
        $this->assertTrue($policy->moderate($administrator, $organization));
    }

    public function test_update_and_moderate_allow_moderator_within_assigned_scope_and_deny_outside_scope(): void
    {
        $policy = new OrganizationPolicy;

        $moderator = Trooper::factory()->asModerator()->create();

        $root = Organization::factory()->withNodePath('org.root')->create();
        $descendant = Organization::factory()->withParent($root)->withNodePath('org.root.unit')->create();
        $outside = Organization::factory()->withNodePath('org.other')->create();

        TrooperAssignment::factory()
            ->forTrooper($moderator)
            ->forOrganization($root)
            ->asModerator()
            ->create();

        $this->assertTrue($policy->update($moderator, $descendant));
        $this->assertTrue($policy->moderate($moderator, $descendant));
        $this->assertFalse($policy->update($moderator, $outside));
        $this->assertFalse($policy->moderate($moderator, $outside));
    }

    public function test_delete_restore_and_force_delete_are_denied(): void
    {
        $policy = new OrganizationPolicy;

        $administrator = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        $this->assertFalse($policy->delete($administrator, $organization));
        $this->assertFalse($policy->restore($administrator, $organization));
        $this->assertFalse($policy->forceDelete($administrator, $organization));
    }
}
