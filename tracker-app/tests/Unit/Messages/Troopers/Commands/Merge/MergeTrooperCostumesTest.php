<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Commands\Merge;

use App\Messages\Troopers\Commands\Merge\MergeTrooperCostumes;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MergeTrooperCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_transfers_active_and_trashed_costumes_to_target_trooper(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $organization = Organization::factory()->asOrganization()->create();
        $active_costume_type = Costume::factory()->create();
        $trashed_costume_type = Costume::factory()->create();

        $active_organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($active_costume_type)
            ->create();

        $trashed_organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($trashed_costume_type)
            ->create();

        $active_costume = TrooperCostume::factory()
            ->forTrooper($source_trooper)
            ->forOrganizationCostume($active_organization_costume)
            ->create();

        $trashed_costume = TrooperCostume::factory()
            ->forTrooper($source_trooper)
            ->forOrganizationCostume($trashed_organization_costume)
            ->create();
        $trashed_costume->delete();

        MergeTrooperCostumes::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $active_costume->id,
            TrooperCostume::TROOPER_ID => $target_trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $active_organization_costume->id,
            TrooperCostume::DELETED_AT => null,
        ]);

        $this->assertDatabaseHas('tt_trooper_costumes', [
            TrooperCostume::ID => $trashed_costume->id,
            TrooperCostume::TROOPER_ID => $target_trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $trashed_organization_costume->id,
        ]);

        $this->assertSoftDeleted('tt_trooper_costumes', [
            TrooperCostume::ID => $trashed_costume->id,
            TrooperCostume::TROOPER_ID => $target_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_costumes', [
            TrooperCostume::ID => $active_costume->id,
            TrooperCostume::TROOPER_ID => $source_trooper->id,
        ]);

        $this->assertDatabaseMissing('tt_trooper_costumes', [
            TrooperCostume::ID => $trashed_costume->id,
            TrooperCostume::TROOPER_ID => $source_trooper->id,
        ]);
    }

    public function test_call_restores_target_and_merges_duplicate_costume_data(): void
    {
        $target_trooper = Trooper::factory()->asActive()->create();
        $source_trooper = Trooper::factory()->asActive()->create();

        $organization = Organization::factory()->asOrganization()->create();
        $costume = Costume::factory()->create();

        $organization_costume = OrganizationCostume::factory()
            ->forOrganization($organization)
            ->forCostume($costume)
            ->create();

        $target_sync = now()->subDay()->startOfSecond();
        $source_sync = now()->startOfSecond();

        $target_costume = TrooperCostume::factory()
            ->forTrooper($target_trooper)
            ->forOrganizationCostume($organization_costume)
            ->state([
                TrooperCostume::IMAGE_URL_SM => null,
                TrooperCostume::IMAGE_URL_LG => 'target-lg.jpg',
                TrooperCostume::IMAGE_URL_BUCKET_OFF => null,
                TrooperCostume::SYNCHRONIZED_AT => $target_sync,
            ])
            ->create();
        $target_costume->delete();

        $source_costume = TrooperCostume::factory()
            ->forTrooper($source_trooper)
            ->forOrganizationCostume($organization_costume)
            ->state([
                TrooperCostume::IMAGE_URL_SM => 'source-sm.jpg',
                TrooperCostume::IMAGE_URL_LG => 'source-lg.jpg',
                TrooperCostume::IMAGE_URL_BUCKET_OFF => 'bucket-source.jpg',
                TrooperCostume::SYNCHRONIZED_AT => $source_sync,
            ])
            ->create();

        MergeTrooperCostumes::call([
            'target_trooper' => $target_trooper,
            'source_trooper' => $source_trooper,
        ]);

        $merged_target_costume = TrooperCostume::query()->findOrFail($target_costume->id);

        $this->assertNull($merged_target_costume->{TrooperCostume::DELETED_AT});
        $this->assertSame('source-sm.jpg', $merged_target_costume->{TrooperCostume::IMAGE_URL_SM});
        $this->assertSame('source-lg.jpg', $merged_target_costume->{TrooperCostume::IMAGE_URL_LG});
        $this->assertSame('bucket-source.jpg', $merged_target_costume->{TrooperCostume::IMAGE_URL_BUCKET_OFF});
        $this->assertTrue(
            $merged_target_costume->{TrooperCostume::SYNCHRONIZED_AT}?->equalTo($source_sync) ?? false,
        );

        $this->assertDatabaseMissing('tt_trooper_costumes', [
            TrooperCostume::ID => $source_costume->id,
            TrooperCostume::TROOPER_ID => $source_trooper->id,
            TrooperCostume::ORGANIZATION_COSTUME_ID => $organization_costume->id,
        ]);
    }
}