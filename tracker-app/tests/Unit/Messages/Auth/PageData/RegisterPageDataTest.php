<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\PageData;

use App\Enums\MembershipRole;
use App\Messages\Auth\PageData\RegisterPageData;
use App\Messages\Auth\Queries\GetAuthConfig;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Models\Organization;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use Tests\TestCase;

class RegisterPageDataTest extends TestCase
{
    #[RunInSeparateProcess]
    public function test_handle_returns_oauth_organizations_and_allowed_membership_roles(): void
    {
        $oauth = [
            'session' => ['method' => 'email'],
            'google' => ['enabled' => true, 'configured' => true],
        ];

        $organization = $this->makeOrganizationHierarchy();

        Mockery::mock('alias:' . GetAuthConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn($oauth);

        Mockery::mock('alias:' . GetOrganizationHierarchy::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn(collect([$organization]));

        $subject = new RegisterPageData();

        $result = $subject->handle();

        $this->assertSame($oauth, $result['oauth']);
        $this->assertEquals([
            [
                'id' => 10,
                'name' => 'Alpha Organization',
                'identifier_display' => 'AO',
                'requires_guardian' => false,
                'regions' => new Collection([
                    [
                        'id' => 20,
                        'name' => 'Alpha Region',
                        'units' => new Collection([
                            [
                                'id' => 30,
                                'name' => 'Alpha Unit',
                            ],
                        ]),
                    ],
                ]),
            ],
        ], $result['organizations']);
        $this->assertSame(
            MembershipRole::toValueLabels([
                MembershipRole::MODERATOR,
                MembershipRole::ADMINISTRATOR,
            ]),
            $result['membership_roles']
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    private function makeOrganizationHierarchy(): Organization
    {
        $unit = Organization::factory()
            ->asUnit()
            ->withName('Alpha Unit')
            ->make([
                Organization::ID => 30,
            ]);

        $region = Organization::factory()
            ->asRegion()
            ->withName('Alpha Region')
            ->make([
                Organization::ID => 20,
            ]);
        $region->setRelation('organizations', new Collection([$unit]));

        $organization = Organization::factory()
            ->asOrganization()
            ->withName('Alpha Organization')
            ->withIdentifierDisplay('AO')
            ->make([
                Organization::ID => 10,
                Organization::REQUIRES_GUARDIAN => false,
            ]);
        $organization->setRelation('organizations', new Collection([$region]));

        return $organization;
    }
}