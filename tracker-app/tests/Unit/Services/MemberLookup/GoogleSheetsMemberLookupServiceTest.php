<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MemberLookup;

use App\Models\Organization;
use App\Services\GoogleService;
use App\Services\MemberLookup\GoogleSheetsMemberLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

class GoogleSheetsMemberLookupServiceTest extends TestCase
{
    use RefreshDatabase;

    private GoogleService $google;
    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->google = Mockery::mock(GoogleService::class);
        $this->organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNC_SHEET_ID => 'sheet-id-123',
        ]);
    }

    private function makeSubject(
        string $identifier_header = 'ID',
        string $name_header = '',
        string $identifier_prefix = '',
    ): GoogleSheetsMemberLookupService {
        return new GoogleSheetsMemberLookupService(
            $this->organization,
            $this->google,
            'Troopers',
            $identifier_header,
            $name_header,
            $identifier_prefix,
        );
    }

    public function test_lookup_returns_null_when_organization_has_no_sync_sheet_id(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNC_SHEET_ID => null,
        ]);

        $subject = new GoogleSheetsMemberLookupService(
            $organization,
            $this->google,
            'Troopers',
            'ID',
        );

        $this->google->shouldNotReceive('getSheet');

        $result = $subject->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_returns_null_when_google_returns_empty_rows(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([]);

        $result = $this->makeSubject()->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_returns_null_when_identifier_not_in_sheet(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'Name'],
            ['5555', 'Someone Else'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_returns_member_data_when_identifier_found(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'Name'],
            ['1234', 'Jane Rebel'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('1234');

        $this->assertIsArray($result);
        $this->assertSame('1234', $result['identifier']);
        $this->assertSame('1234', $result['formatted_identifier']);
        $this->assertSame('Jane Rebel', $result['full_name']);
        $this->assertSame('Active', $result['status']);
        $this->assertSame('Good', $result['standing']);
        $this->assertTrue($result['is_approved']);
        $this->assertNull($result['unit_name']);
        $this->assertNull($result['profile_url']);
        $this->assertNull($result['thumbnail_url']);
    }

    public function test_lookup_is_case_insensitive_for_identifier_matching(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'Name'],
            ['REBEL-42', 'Jane Rebel'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('rebel-42');

        $this->assertIsArray($result);
        $this->assertSame('rebel-42', $result['identifier']);
    }

    public function test_lookup_resolves_columns_by_header_name_not_position(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['Extra', 'ID', 'Name'],
            ['ignored', '7777', 'Padawan Pete'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('7777');

        $this->assertIsArray($result);
        $this->assertSame('Padawan Pete', $result['full_name']);
    }

    public function test_lookup_strips_identifier_prefix_before_matching(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'IRL Name'],
            ['SG-1234', 'Sam Saber'],
        ]);

        $result = $this->makeSubject('ID', 'IRL Name', 'SG-')->lookup('1234');

        $this->assertIsArray($result);
        $this->assertSame('Sam Saber', $result['full_name']);
    }

    public function test_lookup_prefix_strip_is_case_insensitive(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'IRL Name'],
            ['sg-5678', 'Sam Saber'],
        ]);

        $result = $this->makeSubject('ID', 'IRL Name', 'SG-')->lookup('5678');

        $this->assertIsArray($result);
        $this->assertSame('5678', $result['identifier']);
    }

    public function test_lookup_returns_null_full_name_when_no_name_header_configured(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['Forum Name', 'Droid Name'],
            ['r2builder', 'R2-D2 Replica'],
        ]);

        $result = $this->makeSubject('Forum Name')->lookup('r2builder');

        $this->assertIsArray($result);
        $this->assertNull($result['full_name']);
        $this->assertSame('r2builder', $result['formatted_identifier']);
    }

    public function test_lookup_returns_null_when_identifier_column_header_not_found(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['WrongColumn', 'Name'],
            ['1234', 'Someone'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_caches_found_result_to_avoid_repeat_sheet_fetches(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'Name'],
            ['1234', 'Jane Rebel'],
        ]);

        $subject = $this->makeSubject('ID', 'Name');
        $subject->lookup('1234');
        $subject->lookup('1234');
    }

    public function test_lookup_caches_not_found_result_to_avoid_repeat_sheet_fetches(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            ['ID', 'Name'],
            ['9999', 'Someone Else'],
        ]);

        $subject = $this->makeSubject('ID', 'Name');
        $first = $subject->lookup('1234');
        $second = $subject->lookup('1234');

        $this->assertNull($first);
        $this->assertNull($second);
    }

    public function test_lookup_trims_whitespace_from_header_names(): void
    {
        $this->google->shouldReceive('getSheet')->once()->andReturn([
            [' ID ', ' Name '],
            ['3333', 'Whitespace Trooper'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('3333');

        $this->assertIsArray($result);
        $this->assertSame('Whitespace Trooper', $result['full_name']);
    }
}
