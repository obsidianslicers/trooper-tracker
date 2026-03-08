<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Services\GoogleService;
use App\Services\Synchronizers\DroidBuildersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DroidBuildersServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_synchronize_maps_sheet_row_to_trooper_status_and_costume_sync(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        $google = Mockery::mock(GoogleService::class);

        /** @var DroidBuildersService&\Mockery\MockInterface $subject */
        $subject = Mockery::mock(DroidBuildersService::class, [$organization, $google])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $org_costume = Mockery::mock(OrganizationCostume::class);

        $subject->shouldReceive('getSheetRows')
            ->once()
            ->with('Sheet1')
            ->andReturn([
                ['TK-123', 'Droid Armor', 'https://img.example/droid.jpg'],
            ]);

        $subject->shouldReceive('getOrCreateOrganizationCostume')
            ->once()
            ->with('Droid Armor')
            ->andReturn($org_costume);

        $subject->shouldReceive('getTrooper')
            ->once()
            ->with('TK-123')
            ->andReturn($trooper);

        $subject->shouldReceive('syncTrooperStatus')
            ->once()
            ->with($trooper, MembershipStatus::ACTIVE);

        $subject->shouldReceive('syncTrooperCostume')
            ->once()
            ->with(
                $trooper,
                $org_costume,
                [TrooperCostume::IMAGE_URL_LG => 'https://img.example/droid.jpg']
            );

        $subject->run();
    }

    public function test_synchronize_skips_rows_missing_costume_name_or_identifier(): void
    {
        $organization = Organization::factory()->create();
        $google = Mockery::mock(GoogleService::class);

        /** @var DroidBuildersService&\Mockery\MockInterface $subject */
        $subject = Mockery::mock(DroidBuildersService::class, [$organization, $google])
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $subject->shouldReceive('getSheetRows')
            ->once()
            ->andReturn([
                ['TK-123', '', 'x'],
                ['', 'Armor', 'x'],
            ]);

        $subject->shouldNotReceive('getOrCreateOrganizationCostume');
        $subject->shouldNotReceive('syncTrooperStatus');
        $subject->shouldNotReceive('syncTrooperCostume');

        $subject->run();
    }
}
