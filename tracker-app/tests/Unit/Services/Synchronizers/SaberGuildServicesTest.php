<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Synchronizers;

use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use App\Services\Synchronizers\SaberGuildServices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class SaberGuildServicesTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;
    private MockInterface $google_service;
    private SaberGuildServices $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->organization = Organization::factory()
            ->state([Organization::SYNC_SHEET_ID => 'test-sheet-id'])
            ->create();
        $this->google_service = $this->mock(GoogleService::class);

        $this->subject = new SaberGuildServices(
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
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John Doe', 'Master of Lightsabers', '12345', 'Jedi Knight', 'https://example.com/jedi.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Jedi Knight')->first();
        $this->assertNotNull($org_costume);

        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();
        $this->assertNotNull($trooper_costume);
        $this->assertEquals('https://example.com/jedi.jpg', $trooper_costume->large_image_url);
    }

    public function test_sync_costumes_converts_google_drive_links(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        $google_drive_url = 'https://drive.google.com/file/d/abc123def456/view?usp=drivesdk';
        $sheet_data = [
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John Doe', 'Master', '12345', 'Knight Costume', $google_drive_url],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Knight Costume')->first();
        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();

        $this->assertNotNull($trooper_costume);
        $this->assertEquals('https://drive.google.com/uc?id=abc123def456', $trooper_costume->large_image_url);
    }

    public function test_sync_costumes_handles_malformed_google_drive_urls(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperOrganization::factory()
            ->state([
                TrooperOrganization::TROOPER_ID => $trooper->id,
                TrooperOrganization::ORGANIZATION_ID => $this->organization->id,
                TrooperOrganization::IDENTIFIER => '12345',
            ])
            ->create();

        // URL that doesn't contain the trigger phrase won't be converted
        $non_google_drive_url = 'https://example.com/costume.jpg';
        $sheet_data = [
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John Doe', 'Master', '12345', 'Costume', $non_google_drive_url],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Costume')->first();
        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();

        $this->assertNotNull($trooper_costume);
        $this->assertEquals($non_google_drive_url, $trooper_costume->large_image_url);
    }

    public function test_sync_costumes_skips_empty_costume_names(): void
    {
        $sheet_data = [
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John', 'Title', '12345', '', 'https://example.com/image.jpg'],
            ['Jane', 'Title', '67890', null, null],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_skips_empty_identifier(): void
    {
        $sheet_data = [
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John', 'Title', '', 'Jedi Knight', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_skips_trooper_not_found(): void
    {
        $sheet_data = [
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John', 'Title', '99999', 'Jedi Knight', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        // Organization costume is created, but trooper costume is not
        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, 'Jedi Knight')->first();
        $this->assertNotNull($org_costume);
        $trooper_costumes = TrooperCostume::all();
        $this->assertEquals(0, $trooper_costumes->count());
    }

    public function test_sync_costumes_with_missing_sheet_id(): void
    {
        $this->organization->update([Organization::SYNC_SHEET_ID => null]);

        $this->subject->syncCostumes();

        $org_costumes = OrganizationCostume::all();
        $this->assertEquals(0, $org_costumes->count());
    }

    public function test_sync_costumes_with_invalid_sheet_response(): void
    {
        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn(false);

        $this->subject->syncCostumes();

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
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['12345', '<img src=x>', '12345', '<script>alert("xss")</script>', 'https://example.com/image.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $org_costume = OrganizationCostume::where(OrganizationCostume::NAME, '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;')->first();
        $this->assertNotNull($org_costume);
    }

    public function test_sync_costumes_handles_multiple_costumes_per_trooper(): void
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
            ['header1', 'header2', 'header3', 'header4', 'header5'],
            ['John', 'Master', '12345', 'Jedi Knight', 'https://example.com/jedi.jpg'],
            ['John', 'Master', '12345', 'Sith Lord', 'https://example.com/sith.jpg'],
        ];

        $this->google_service
            ->shouldReceive('getSheet')
            ->with('test-sheet-id', 'Costumes')
            ->andReturn($sheet_data);

        $this->subject->syncCostumes();

        $trooper_costumes = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)->get();
        $this->assertEquals(2, $trooper_costumes->count());
    }

    public function test_sync_all_members_not_implemented(): void
    {
        $this->subject->syncAllMembers();
        $this->assertTrue(true);
    }

    public function test_sync_member_not_implemented(): void
    {
        $this->subject->syncMember('12345');
        $this->assertTrue(true);
    }
}
