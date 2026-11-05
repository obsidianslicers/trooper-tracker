<?php

declare(strict_types=1);

namespace Tests\Unit\Services\MemberLookup;

use App\Models\Organization;
use App\Services\MemberLookup\MemberLookupResolver;
use App\Services\MemberLookup\SheetMemberLookupService;
use App\Services\MemberLookup\TheLegionMemberLookupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberLookupResolverTest extends TestCase
{
    use RefreshDatabase;

    private MemberLookupResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->app->make(MemberLookupResolver::class);
    }

    public function test_resolve_returns_the_legion_service_for_501st_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => '501st',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(TheLegionMemberLookupService::class, $result);
    }

    public function test_resolve_returns_sheet_service_for_rebel_legion_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => 'rebel-legion',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(SheetMemberLookupService::class, $result);
    }

    public function test_resolve_returns_sheet_service_for_saber_guild_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => 'saber-guild',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(SheetMemberLookupService::class, $result);
    }

    public function test_resolve_returns_sheet_service_for_droid_builders_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => 'droid-builders',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(SheetMemberLookupService::class, $result);
    }

    public function test_resolve_returns_sheet_service_for_mandalorian_mercs_org(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => 'mandalorian-mercs',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertInstanceOf(SheetMemberLookupService::class, $result);
    }

    public function test_resolve_returns_null_without_a_synchronizer(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => null,
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertNull($result);
    }

    public function test_resolve_returns_null_for_an_unregistered_synchronizer(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create([
            Organization::SYNCHRONIZER => 'not-registered',
        ]);

        $result = $this->subject->resolve($organization);

        $this->assertNull($result);
    }
}
