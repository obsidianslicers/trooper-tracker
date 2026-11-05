<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Commands\Merge;

use App\Messages\Costumes\Commands\Merge\MergeOrganizationCostumes;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MergeOrganizationCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_repoints_club_links_the_target_does_not_have(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $active = OrganizationCostume::factory()->forOrganization($organization)->forCostume($source)->create();
        $other_organization = Organization::factory()->asOrganization()->create();
        $trashed = OrganizationCostume::factory()
            ->forOrganization($other_organization)
            ->forCostume($source)
            ->create();
        $trashed->delete();

        MergeOrganizationCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertDatabaseHas('tt_organization_costumes', [
            OrganizationCostume::ID => $active->id,
            OrganizationCostume::COSTUME_ID => $target->id,
            OrganizationCostume::DELETED_AT => null,
        ]);
        $this->assertSoftDeleted('tt_organization_costumes', [
            OrganizationCostume::ID => $trashed->id,
            OrganizationCostume::COSTUME_ID => $target->id,
        ]);
    }

    public function test_call_merges_into_an_existing_club_link_and_moves_its_troopers(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $trooper = Trooper::factory()->asActive()->create();
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $target_link = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($target)
            ->create([OrganizationCostume::SYNCHRONIZED_AT => Carbon::parse('2026-01-01')]);
        $source_link = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($source)
            ->withPrefix('TK')
            ->create([OrganizationCostume::SYNCHRONIZED_AT => Carbon::parse('2026-06-01')]);
        $trooper_costume = TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($source_link)
            ->create();

        MergeOrganizationCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertDatabaseMissing('tt_organization_costumes', [
            OrganizationCostume::ID => $source_link->id,
        ]);
        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $target_link->id,
        ]);

        $target_link->refresh();
        $this->assertSame('TK', $target_link->prefix);
        $this->assertTrue($target_link->synchronized_at->equalTo(Carbon::parse('2026-06-01')));
    }

    public function test_call_restores_a_trashed_target_link_when_the_source_link_is_active(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $target_link = OrganizationCostume::factory()->forOrganization($organization)->forCostume($target)->create();
        $target_link->delete();
        OrganizationCostume::factory()->forOrganization($organization)->forCostume($source)->create();

        MergeOrganizationCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertDatabaseHas('tt_organization_costumes', [
            OrganizationCostume::ID => $target_link->id,
            OrganizationCostume::DELETED_AT => null,
        ]);
    }

    public function test_call_keeps_the_target_prefix_when_both_links_have_one(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $target = Costume::factory()->create();
        $source = Costume::factory()->create();

        $target_link = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($target)
            ->withPrefix('TK')
            ->create();
        OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($source)
            ->withPrefix('XX')
            ->create();

        MergeOrganizationCostumes::call(target_costume: $target, source_costume: $source);

        $this->assertSame('TK', $target_link->fresh()->prefix);
    }
}
