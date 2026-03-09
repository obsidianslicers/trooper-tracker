<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Services\GoogleService;
use App\Services\Synchronizers\RebelLegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RebelLegionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_synchronize_maps_sheet_row_to_trooper_status_and_costume_sync(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        $google = Mockery::mock(GoogleService::class);

        /** @var RebelLegionService&\Mockery\MockInterface $subject */
        $subject = Mockery::mock(RebelLegionService::class, [$organization, $google])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $org_costume = Mockery::mock(OrganizationCostume::class);

        $subject->shouldReceive('getSheetRows')
            ->once()
            ->with('Costumes')
            ->andReturn([
                ['RL-77', 'Jedi Robe', 'https://img.example/jedi.jpg'],
            ]);

        $subject->shouldReceive('getOrCreateOrganizationCostume')
            ->once()
            ->with('Jedi Robe')
            ->andReturn($org_costume);

        $subject->shouldReceive('getTrooper')
            ->once()
            ->with('RL-77')
            ->andReturn($trooper);

        $subject->shouldReceive('syncTrooperStatus')
            ->once()
            ->with($trooper, MembershipStatus::ACTIVE);

        $subject->shouldReceive('syncTrooperCostume')
            ->once()
            ->with(
                $trooper,
                $org_costume,
                [TrooperCostume::IMAGE_URL_LG => 'https://img.example/jedi.jpg']
            );

        $subject->run();
    }
}
