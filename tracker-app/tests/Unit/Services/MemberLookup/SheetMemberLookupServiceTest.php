<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MemberLookup;

use App\Exceptions\SheetUnavailableException;
use App\Models\Organization;
use App\Plugins\PluginSheetSource;
use App\Services\MemberLookup\SheetMemberLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Mockery\MockInterface;
use RuntimeException;
use Tests\Concerns\UsesFakeSheetSource;
use Tests\TestCase;

class SheetMemberLookupServiceTest extends TestCase
{
    use RefreshDatabase;
    use UsesFakeSheetSource;

    private MockInterface $source;

    private Organization $organization;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();

        $this->source = $this->fakeSheetSource();
        $this->organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->create();
    }

    private function makeSubject(
        string $identifier_header = 'ID',
        string $name_header = '',
        string $identifier_prefix = '',
    ): SheetMemberLookupService {
        return new SheetMemberLookupService(
            $this->organization,
            app(PluginSheetSource::class),
            'Troopers',
            $identifier_header,
            $name_header,
            $identifier_prefix,
        );
    }

    public function test_lookup_returns_null_when_organization_has_no_sheet(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn(null);

        $this->assertNull($this->makeSubject()->lookup('1234'));
    }

    public function test_lookup_returns_null_without_a_sheet_source(): void
    {
        $this->app->forgetInstance(PluginSheetSource::class);

        $this->assertNull($this->makeSubject()->lookup('1234'));
    }

    public function test_lookup_returns_null_when_the_sheet_is_empty(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([]);

        $result = $this->makeSubject()->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_returns_null_when_identifier_not_in_sheet(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['ID', 'Name'],
            ['5555', 'Someone Else'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_returns_member_data_when_identifier_found(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
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
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['ID', 'Name'],
            ['REBEL-42', 'Jane Rebel'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('rebel-42');

        $this->assertIsArray($result);
        $this->assertSame('rebel-42', $result['identifier']);
    }

    public function test_lookup_resolves_columns_by_header_name_not_position(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['Extra', 'ID', 'Name'],
            ['ignored', '7777', 'Padawan Pete'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('7777');

        $this->assertIsArray($result);
        $this->assertSame('Padawan Pete', $result['full_name']);
    }

    public function test_lookup_strips_identifier_prefix_before_matching(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['ID', 'IRL Name'],
            ['SG-1234', 'Sam Saber'],
        ]);

        $result = $this->makeSubject('ID', 'IRL Name', 'SG-')->lookup('1234');

        $this->assertIsArray($result);
        $this->assertSame('Sam Saber', $result['full_name']);
    }

    public function test_lookup_prefix_strip_is_case_insensitive(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['ID', 'IRL Name'],
            ['sg-5678', 'Sam Saber'],
        ]);

        $result = $this->makeSubject('ID', 'IRL Name', 'SG-')->lookup('5678');

        $this->assertIsArray($result);
        $this->assertSame('5678', $result['identifier']);
    }

    public function test_lookup_returns_null_full_name_when_no_name_header_configured(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
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
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['WrongColumn', 'Name'],
            ['1234', 'Someone'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_caches_found_result_to_avoid_repeat_sheet_fetches(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['ID', 'Name'],
            ['1234', 'Jane Rebel'],
        ]);

        $subject = $this->makeSubject('ID', 'Name');
        $subject->lookup('1234');
        $subject->lookup('1234');
    }

    public function test_lookup_caches_not_found_result_to_avoid_repeat_sheet_fetches(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            ['ID', 'Name'],
            ['9999', 'Someone Else'],
        ]);

        $subject = $this->makeSubject('ID', 'Name');
        $first = $subject->lookup('1234');
        $second = $subject->lookup('1234');

        $this->assertNull($first);
        $this->assertNull($second);
    }

    public function test_lookup_returns_null_without_caching_when_the_sheet_is_unavailable(): void
    {
        $this->source->shouldReceive('rows')
            ->twice()
            ->andThrow(new SheetUnavailableException('The sheet is unavailable.'));

        $subject = $this->makeSubject('ID', 'Name');

        $this->assertNull($subject->lookup('1234'));
        $this->assertNull($subject->lookup('1234'));
    }

    public function test_lookup_returns_null_instead_of_throwing_when_the_source_fails(): void
    {
        $this->source->shouldReceive('rows')
            ->once()
            ->andThrow(new InvalidArgumentException('file "google-credentials.json" does not exist'));

        $result = $this->makeSubject('ID', 'Name')->lookup('1234');

        $this->assertNull($result);
    }

    public function test_lookup_does_not_cache_a_failed_sheet_read(): void
    {
        $this->source->shouldReceive('rows')
            ->twice()
            ->andThrow(new RuntimeException('Google API unreachable'));

        $subject = $this->makeSubject('ID', 'Name');
        $subject->lookup('1234');
        $subject->lookup('1234');
    }

    public function test_lookup_trims_whitespace_from_header_names(): void
    {
        $this->source->shouldReceive('rows')->once()->andReturn([
            [' ID ', ' Name '],
            ['3333', 'Whitespace Trooper'],
        ]);

        $result = $this->makeSubject('ID', 'Name')->lookup('3333');

        $this->assertIsArray($result);
        $this->assertSame('Whitespace Trooper', $result['full_name']);
    }
}
