<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Synchronizers;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use App\Services\Synchronizers\RebelLegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class RebelLegionServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private MockInterface $google_service;
    private RebelLegionService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()
            ->state([Organization::SYNC_SHEET_ID => 'test-sheet-id'])
            ->create();
        $this->google_service = $this->mock(GoogleService::class);

        $this->subject = new RebelLegionService(
            $this->organization,
            $this->google_service
        );
    }

    public function test_sync_costumes_with_valid_sheet_data(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['12345', 'Stormtrooper', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($org_costume);
        $this->assertTrue($org_costume->synchronized_at !== null);

        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();
        $this->assertNotNull($trooper_costume);
        $this->assertEquals('https://example.com/image.jpg', $trooper_costume->large_image_url);
    }

    public function test_sync_costumes_skips_empty_costume_names(): void
    {
        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['12345', '', null],
            ['67890', null, 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_skips_empty_identifier(): void
    {
        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['', 'Stormtrooper', 'https://example.com/image.jpg'],
            [null, 'TIE Pilot', null],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_skips_trooper_not_found(): void
    {
        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['99999', 'Stormtrooper', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        // Organization costume is created, but trooper costume is not
        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $this->assertNotNull($org_costume);
        $trooper_costumes = TrooperCostume::all();
        $this->assertEquals(0, $trooper_costumes->count());
    }

    public function test_sync_costumes_with_missing_sheet_id(): void
    {
        $this->organization->update([Organization::SYNC_SHEET_ID => null]);

        $this->subject->synchronize();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_with_invalid_sheet_response(): void
    {
        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn(false);

        $this->subject->synchronize();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_sanitizes_input(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['12345', '<script>alert("xss")</script>', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')->first();
        $this->assertNotNull($org_costume);
    }

    public function test_sync_costumes_handles_multiple_troopers(): void
    {
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper1->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper2->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '67890',
            ])
            ->create();

        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['12345', 'Stormtrooper', 'https://example.com/stormtrooper.jpg'],
            ['67890', 'TIE Pilot', 'https://example.com/tie_pilot.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $stormtrooper_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Stormtrooper')->first();
        $tie_pilot_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'TIE Pilot')->first();

        $this->assertNotNull($stormtrooper_costume);
        $this->assertNotNull($tie_pilot_costume);

        $trooper1_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper1->id)->get();
        $trooper2_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper2->id)->get();

        $this->assertEquals(1, $trooper1_costumes->count());
        $this->assertEquals(1, $trooper2_costumes->count());
    }

    public function test_sync_costumes_updates_existing_costume(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $org_costume = OrganizationCostume::factory()
            ->state([
                OrganizationCostume::ORGANIZATION_ID => $this->organization->id,
                OrganizationCostume::NAME => 'Stormtrooper',
            ])
            ->create();

        TrooperCostume::factory()
            ->state([
                TrooperCostume::TROOPER_ID => $trooper->id,
                TrooperCostume::COSTUME_ID => $org_costume->id,
                TrooperCostume::LARGE_IMAGE_URL => 'https://example.com/old_image.jpg',
            ])
            ->create();

        $sheet_data = [
            ['header1', 'header2', 'header3'],
            ['12345', 'Stormtrooper', 'https://example.com/new_image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();

        $this->assertNotNull($trooper_costume);
        $this->assertEquals('https://example.com/new_image.jpg', $trooper_costume->large_image_url);
    }
}
