<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\Synchronizers\RebelLegionService;
use App\Enums\OrganizationType;

class RebelLegionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_all_members_updates_pivot_display_name_and_verified_at()
    {
        // Create organization and trooper, attach pivot with forum identifier
        $org = Organization::create([
            'name' => 'Rebel Legion',
            'type' => OrganizationType::ORGANIZATION,
            'service_class' => RebelLegionService::class,
            'sync_sheet_id' => 'sheet123',
        ]);

        $trooper = Trooper::factory()->create();
        $org->troopers()->attach($trooper->id, ['identifier' => 'forumUser']);

        // Prepare fake sheet rows: header + one row (legionId, name, rebelforum)
        $sheetRows = [
            ['legionId', 'name', 'rebelforum'],
            ['12345', 'Luke Skywalker', 'forumUser'],
        ];

        // Mock GoogleService so getSheet returns our rows
        $mockGoogle = Mockery::mock(\App\Services\GoogleService::class);
        $mockGoogle->shouldReceive('getSheet')
            ->with('sheet123', 'Troopers')
            ->andReturn($sheetRows);

        // Bind the mock into the container
        $this->app->instance(\App\Services\GoogleService::class, $mockGoogle);

        // Instantiate service and run sync
        $service = app()->make(RebelLegionService::class, ['organization' => $org]);
        $service->syncAllMembers();

        // Assert pivot row in tt_trooper_organizations was updated with display_name
        $this->assertDatabaseHas('tt_trooper_organizations', [
            'trooper_id' => $trooper->id,
            'organization_id' => $org->id,
            'display_name' => 'Luke Skywalker',
        ]);

        // If verified_at exists, assert it's set
        if (\Illuminate\Support\Facades\Schema::hasColumn('tt_trooper_organizations', 'verified_at')) {
            $row = \DB::table('tt_trooper_organizations')
                ->where('trooper_id', $trooper->id)
                ->where('organization_id', $org->id)
                ->first();

            $this->assertNotNull($row->verified_at);
        }
    }
}
