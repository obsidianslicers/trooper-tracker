<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Synchronizers;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use App\Services\Synchronizers\DroidBuildersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class DroidBuildersServiceTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private MockInterface $google_service;
    private DroidBuildersService $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()
            ->state([Organization::SYNC_SHEET_ID => 'test-sheet-id'])
            ->create();
        $this->google_service = $this->mock(GoogleService::class);

        $this->subject = new DroidBuildersService(
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
            ['12345', 'R2-D2', 'https://example.com/r2d2.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'R2-D2')->first();
        $this->assertNotNull($org_costume);
        $this->assertTrue($org_costume->synchronized_at !== null);

        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();
        $this->assertNotNull($trooper_costume);
        $this->assertEquals('https://example.com/r2d2.jpg', $trooper_costume->image_url_lg);
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
            ['', 'R2-D2', 'https://example.com/r2d2.jpg'],
            [null, 'C-3PO', null],
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
            ['99999', 'R2-D2', 'https://example.com/r2d2.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        // Organization costume is created, but trooper costume is not
        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'R2-D2')->first();
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
            ['12345', '<img src=x onerror="alert(1)">', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, '&lt;img src=x onerror=&quot;alert(1)&quot;&gt;')->first();
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
            ['12345', 'R2-D2', 'https://example.com/r2d2.jpg'],
            ['67890', 'C-3PO', 'https://example.com/c3po.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $r2d2_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'R2-D2')->first();
        $c3po_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'C-3PO')->first();

        $this->assertNotNull($r2d2_costume);
        $this->assertNotNull($c3po_costume);

        $trooper1_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper1->id)->get();
        $trooper2_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper2->id)->get();

        $this->assertEquals(1, $trooper1_costumes->count());
        $this->assertEquals(1, $trooper2_costumes->count());
    }

    public function test_sync_costumes_updates_existing_trooper_costume(): void
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
                OrganizationCostume::NAME => 'R2-D2',
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
            ['12345', 'R2-D2', 'https://example.com/new_image.jpg'],
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
        $this->assertEquals('https://example.com/new_image.jpg', $trooper_costume->image_url_lg);
    }

    public function test_sync_costumes_with_null_image_url(): void
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
            ['12345', 'R2-D2', null],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->synchronize();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'R2-D2')->first();
        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();

        $this->assertNotNull($trooper_costume);
        // cleanInput(null) now returns null
        $this->assertNull($trooper_costume->image_url_lg);
    }
}
