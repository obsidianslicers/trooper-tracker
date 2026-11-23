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
            'session' => ['method' => 'google', 'email' => 'alpha@example.com', 'provider_id' => 'abc'],
            'providers' => [],
            'required' => null,
            'email_password' => ['enabled' => true],
        ];

        $organization = $this->makeOrganizationHierarchy();

        Mockery::mock('alias:'.GetAuthConfig::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn($oauth);

        Mockery::mock('alias:'.GetOrganizationHierarchy::class)
            ->shouldReceive('call')
            ->once()
            ->andReturn(collect([$organization]));

        $subject = new RegisterPageData;

        $result = json_decode(json_encode($subject->handle()), true);

        //  only the email from the sign-up session; not the method or provider id
        $this->assertSame(['session' => ['email' => 'alpha@example.com']], $result['oauth']);
        $this->assertSame([
            [
                'id' => 10,
                'name' => 'Alpha Organization',
                'identifier_display' => 'AO',
                'requires_guardian' => false,
                'regions' => [
                    [
                        'id' => 20,
                        'name' => 'Alpha Region',
                        'units' => [
                            [
                                'id' => 30,
                                'name' => 'Alpha Unit',
                            ],
                        ],
                    ],
                ],
            ],
        ], $result['organizations']);
        $this->assertSame(
            MembershipRole::toValueLabels([
                MembershipRole::EVENT_COORDINATOR,
                MembershipRole::MODERATOR,
                MembershipRole::STAFF_OFFICER,
                MembershipRole::SYSTEM_OWNER,
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
