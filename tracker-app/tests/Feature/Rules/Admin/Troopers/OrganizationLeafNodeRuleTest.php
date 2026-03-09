<?php

declare(strict_types=1);

namespace Tests\Feature\Rules\Admin\Troopers;

use App\Models\Organization;
use App\Rules\Admin\Troopers\OrganizationLeafNodeRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class OrganizationLeafNodeRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_when_selected_organization_is_descendant_leaf_node(): void
    {
        $parent = Organization::factory()->withNodePath('root')->create();

        $leaf = Organization::factory()
            ->withParent($parent)
            ->withNodePath('root.region-1')
            ->create();

        $validator = Validator::make([
            'organization_id' => $leaf->id,
        ], [
            'organization_id' => [new OrganizationLeafNodeRule($parent)],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_selected_organization_is_not_descendant_of_parent(): void
    {
        $parent = Organization::factory()->withName('501st')->withNodePath('root')->create();

        $outside = Organization::factory()
            ->withNodePath('other.club')
            ->create();

        $validator = Validator::make([
            'organization_id' => $outside->id,
        ], [
            'organization_id' => [new OrganizationLeafNodeRule($parent)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame(
            'The selected organization must be within 501st.',
            $validator->errors()->first('organization_id')
        );
    }

    public function test_fails_when_selected_organization_has_children(): void
    {
        $parent = Organization::factory()->withNodePath('root')->create();

        $region = Organization::factory()
            ->withParent($parent)
            ->withNodePath('root.region-2')
            ->create();

        Organization::factory()
            ->withParent($region)
            ->withNodePath('root.region-2.unit-1')
            ->create();

        $validator = Validator::make([
            'organization_id' => $region->id,
        ], [
            'organization_id' => [new OrganizationLeafNodeRule($parent)],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertSame('Please select a more specific unit.', $validator->errors()->first('organization_id'));
    }

    public function test_passes_when_value_is_empty_or_unknown_organization(): void
    {
        $parent = Organization::factory()->withNodePath('root')->create();

        $empty_validator = Validator::make([
            'organization_id' => null,
        ], [
            'organization_id' => [new OrganizationLeafNodeRule($parent)],
        ]);

        $unknown_validator = Validator::make([
            'organization_id' => 999999,
        ], [
            'organization_id' => [new OrganizationLeafNodeRule($parent)],
        ]);

        $this->assertTrue($empty_validator->passes());
        $this->assertTrue($unknown_validator->passes());
    }
}
