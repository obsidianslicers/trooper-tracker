<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Costumes\Commands\Merge;

use App\Messages\Costumes\Commands\Merge\MergeOrganizationCostumeTroopers;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeOrganizationCostumeTroopersTest extends TestCase
{
    use RefreshDatabase;

    private OrganizationCostume $target_link;

    private OrganizationCostume $source_link;

    protected function setUp(): void
    {
        parent::setUp();

        $organization = Organization::factory()->asOrganization()->create();

        $this->target_link = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume(Costume::factory()->create())
            ->create();
        $this->source_link = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume(Costume::factory()->create())
            ->create();
    }

    public function test_call_repoints_troopers_who_only_have_the_source(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $trooper_costume = $this->trooperCostume($trooper, $this->source_link);

        $this->merge();

        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trooper_costume->id,
            TrooperCostume::TROOPER_ID => $trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $this->target_link->id,
        ]);
    }

    public function test_call_keeps_the_target_record_for_troopers_who_have_both(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $target_costume = $this->trooperCostume($trooper, $this->target_link, [
            TrooperCostume::IMAGE_URL_SM => 'target-sm.jpg',
            TrooperCostume::IMAGE_URL_LG => null,
        ]);
        $source_costume = $this->trooperCostume($trooper, $this->source_link, [
            TrooperCostume::IMAGE_URL_SM => 'source-sm.jpg',
            TrooperCostume::IMAGE_URL_LG => 'source-lg.jpg',
        ]);

        $this->merge();

        $this->assertDatabaseMissing('tt_trooper_costumes', [TrooperCostume::ID => $source_costume->id]);

        $target_costume->refresh();
        $this->assertSame('target-sm.jpg', $target_costume->image_url_sm);
        $this->assertSame('source-lg.jpg', $target_costume->image_url_lg);
    }

    public function test_call_moves_display_costume_to_the_kept_record(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $target_costume = $this->trooperCostume($trooper, $this->target_link);
        $source_costume = $this->trooperCostume($trooper, $this->source_link);
        $trooper->display_costume_id = $source_costume->id;
        $trooper->save();

        $this->merge();

        $this->assertSame($target_costume->id, $trooper->fresh()->display_costume_id);
    }

    public function test_call_restores_a_trashed_target_record_when_the_source_is_active(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $target_costume = $this->trooperCostume($trooper, $this->target_link);
        $target_costume->delete();
        $this->trooperCostume($trooper, $this->source_link);

        $this->merge();

        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $target_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);
    }

    public function test_call_leaves_a_trashed_target_record_trashed_when_the_source_is_trashed(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $target_costume = $this->trooperCostume($trooper, $this->target_link);
        $target_costume->delete();
        $this->trooperCostume($trooper, $this->source_link)->delete();

        $this->merge();

        $this->assertSoftDeleted('tt_trooper_costumes', [TrooperCostume::ID => $target_costume->id]);
    }

    private function merge(): void
    {
        MergeOrganizationCostumeTroopers::call(
            target_organization_costume: $this->target_link,
            source_organization_costume: $this->source_link,
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function trooperCostume(
        Trooper $trooper,
        OrganizationCostume $link,
        array $attributes = [],
    ): TrooperCostume {
        return TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($link)
            ->create($attributes);
    }
}
