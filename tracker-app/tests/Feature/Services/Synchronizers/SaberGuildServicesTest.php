<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Services\GoogleService;
use App\Services\Synchronizers\SaberGuildServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SaberGuildServicesTest extends TestCase
{
    use RefreshDatabase;

    public function test_synchronize_converts_drive_url_and_syncs_trooper_costume(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        $google = Mockery::mock(GoogleService::class);

        /** @var SaberGuildServices&\Mockery\MockInterface $subject */
        $subject = Mockery::mock(SaberGuildServices::class, [$organization, $google])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $org_costume = Mockery::mock(OrganizationCostume::class);

        $subject->shouldReceive('getSheetRows')
            ->once()
            ->with('Troopers')
            ->andReturn([
                ['Name', 'Rank', 'SG-1', 'Saber Gear', 'https://drive.google.com/file/d/abc123/view?usp=drivesdk'],
            ]);

        $subject->shouldReceive('getOrCreateOrganizationCostume')
            ->once()
            ->with('Saber Gear')
            ->andReturn($org_costume);

        $subject->shouldReceive('getTrooper')
            ->once()
            ->with('SG-1')
            ->andReturn($trooper);

        $subject->shouldReceive('syncTrooperStatus')
            ->once()
            ->with($trooper, MembershipStatus::ACTIVE);

        $subject->shouldReceive('syncTrooperCostume')
            ->once()
            ->with(
                $trooper,
                $org_costume,
                [TrooperCostume::IMAGE_URL_LG => 'https://drive.google.com/uc?id=abc123']
            );

        $subject->run();
    }
}
