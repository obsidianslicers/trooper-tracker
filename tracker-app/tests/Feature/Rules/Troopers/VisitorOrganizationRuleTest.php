<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Troopers;

use App\Enums\MembershipRole;
use App\Models\Organization;
use App\Models\Trooper;
use App\Rules\Troopers\VisitorOrganizationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class VisitorOrganizationRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_for_non_visitor_trooper(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);
        $parent = Organization::factory()->asOrganization()->create();
        $sub_org = Organization::factory()->asRegion()->withParent($parent)->create();

        $validator = Validator::make(
            ['organization_id' => $sub_org->id],
            ['organization_id' => [new VisitorOrganizationRule($trooper)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_passes_for_visitor_assigned_to_top_level_org(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);
        $top_level_org = Organization::factory()->asOrganization()->create();

        $validator = Validator::make(
            ['organization_id' => $top_level_org->id],
            ['organization_id' => [new VisitorOrganizationRule($trooper)]]
        );

        $this->assertFalse($validator->fails());
    }

    public function test_fails_for_visitor_assigned_to_sub_organization(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
        ]);
        $parent = Organization::factory()->asOrganization()->create();
        $sub_org = Organization::factory()->asRegion()->withParent($parent)->create();

        $validator = Validator::make(
            ['organization_id' => $sub_org->id],
            ['organization_id' => [new VisitorOrganizationRule($trooper)]]
        );

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'Visitors can only join top-level organizations.',
            $validator->errors()->first('organization_id')
        );
    }
}
