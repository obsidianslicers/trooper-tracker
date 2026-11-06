<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Queries;

use App\Messages\Organizations\Queries\GetOrganizationsForPicker;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetOrganizationsForPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_with_moderated_only_returns_moderated_hierarchy_by_sequence(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $moderated_root = Organization::factory()
            ->asOrganization()
            ->withName('Moderated Root')
            ->withNodePath('100.')
            ->withSequence(200)
            ->create();

        $moderated_child = Organization::factory()
            ->asRegion()
            ->withParent($moderated_root)
            ->withName('Moderated Child')
            ->withNodePath('100.200.')
            ->withSequence(300)
            ->create();

        $outside_organization = Organization::factory()
            ->asOrganization()
            ->withName('Outside Organization')
            ->withNodePath('900.')
            ->withSequence(100)
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($moderated_root)
            ->asModerator()
            ->create();

        $result = (new GetOrganizationsForPicker($trooper, ['moderated_only' => true]))->handle();

        $this->assertSame(
            [$moderated_root->id, $moderated_child->id],
            $result->pluck(Organization::ID)->all()
        );
        $this->assertNotContains($outside_organization->id, $result->pluck(Organization::ID)->all());
    }

    public function test_handle_with_organization_id_returns_selected_organization_and_descendants(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $selected = Organization::factory()
            ->asOrganization()
            ->withName('Selected Root')
            ->withNodePath('500.')
            ->withSequence(200)
            ->create();

        $descendant = Organization::factory()
            ->asRegion()
            ->withParent($selected)
            ->withName('Selected Descendant')
            ->withNodePath('500.600.')
            ->withSequence(300)
            ->create();

        $outside = Organization::factory()
            ->asOrganization()
            ->withName('Outside Root')
            ->withNodePath('800.')
            ->withSequence(100)
            ->create();

        $result = (new GetOrganizationsForPicker($trooper, [
            'organization_id' => $selected->id,
        ]))->handle();

        $this->assertSame([$selected->id, $descendant->id], $result->pluck(Organization::ID)->all());
        $this->assertNotContains($outside->id, $result->pluck(Organization::ID)->all());
    }

    public function test_handle_without_filters_returns_all_organizations_by_sequence(): void
    {
        $trooper = Trooper::factory()->asMember()->create();

        $second = Organization::factory()->asOrganization()->withSequence(200)->create();
        $first = Organization::factory()->asOrganization()->withSequence(100)->create();
        $third = Organization::factory()->asOrganization()->withSequence(300)->create();

        $result = (new GetOrganizationsForPicker($trooper, []))->handle();

        $this->assertSame([$first->id, $second->id, $third->id], $result->pluck(Organization::ID)->all());
    }

    public function test_handle_with_depth_max_drops_deeper_organizations(): void
    {
        $trooper = Trooper::factory()->create();
        $club = Organization::factory()->asOrganization()->create();
        $region = Organization::factory()->asRegion()->withParent($club)->create();
        Organization::factory()->asUnit()->withParent($region)->create();

        $result = (new GetOrganizationsForPicker($trooper, ['depth_max' => $region->fresh()->depth]))->handle();

        $this->assertEqualsCanonicalizing([$club->id, $region->id], $result->pluck('id')->all());
    }
}
