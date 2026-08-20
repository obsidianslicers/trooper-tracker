<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Troopers\MemberOrganizationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class MemberOrganizationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_for_visitor_trooper(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);
        $parent = Organization::factory()->asOrganization()->create();
        $child = Organization::factory()->asRegion()->withParent($parent)->create();

        $validator = Validator::make(
            ['organization_id' => $child->id],
            ['organization_id' => [new MemberOrganizationRule($trooper)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_passes_for_member_assigned_to_leaf_organization(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);
        $organization = Organization::factory()->asOrganization()->create();

        $validator = Validator::make(
            ['organization_id' => $organization->id],
            ['organization_id' => [new MemberOrganizationRule($trooper)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_fails_for_member_assigned_to_parent_organization_with_children(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);
        $parent = Organization::factory()->asOrganization()->create();
        Organization::factory()->asRegion()->withParent($parent)->create();

        $validator = Validator::make(
            ['organization_id' => $parent->id],
            ['organization_id' => [new MemberOrganizationRule($trooper)]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Members must join at the lowest-level organization.',
            $validator->errors()->first('organization_id')
        );
    }
}
