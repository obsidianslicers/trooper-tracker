<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MemberLookup;

use App\Models\Organization;
use App\Services\GoogleService;
use App\Services\MemberLookup\GoogleSheetsMemberLookupService;
use App\Services\MemberLookup\MemberLookupResolver;
use App\Services\MemberLookup\TheLegionMemberLookupService;
use App\Services\Synchronizers\DroidBuildersService;
use App\Services\Synchronizers\MandalorianMercsService;
use App\Services\Synchronizers\RebelLegionService;
use App\Services\Synchronizers\SaberGuildServices;
use App\Services\Synchronizers\TheLegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MemberLookupResolverTest extends TestCase
{
    use RefreshDatabase;

    private MemberLookupResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $google = Mockery::mock(GoogleService::class);
        $this->subject = new MemberLookupResolver($google);
    }

    public function test_resolve_returns_the_legion_service_for_501st_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => TheLegionService::class,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(TheLegionMemberLookupService::class, $result);
    }

    public function test_resolve_returns_google_sheets_service_for_rebel_legion_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => RebelLegionService::class,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(GoogleSheetsMemberLookupService::class, $result);
    }

    public function test_resolve_returns_google_sheets_service_for_saber_guild_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => SaberGuildServices::class,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(GoogleSheetsMemberLookupService::class, $result);
    }

    public function test_resolve_returns_google_sheets_service_for_droid_builders_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => DroidBuildersService::class,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(GoogleSheetsMemberLookupService::class, $result);
    }

    public function test_resolve_returns_google_sheets_service_for_mandalorian_mercs_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => MandalorianMercsService::class,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(GoogleSheetsMemberLookupService::class, $result);
    }

    public function test_resolve_returns_null_for_unknown_service_class(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => null,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertNull($result);
    }

    public function test_resolve_returns_null_for_unrecognized_service_class(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SERVICE_CLASS => 'App\Services\Synchronizers\UnknownService',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertNull($result);
    }
}
