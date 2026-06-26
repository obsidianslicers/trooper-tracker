<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use App\Services\Synchronizers\TheLegionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class TheLegionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_syncs_costumes_and_trooper_data_from_remote_sources(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-421',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::NONE,
        ]);

        Http::fake([
            'https://crls.501st.com/costume-reference-library/costumes-by-name' => Http::response(
                '<article><a>AR - ARC Trooper</a></article>',
                200
            ),
            'https://www.501st.com/memberAPI/v3/legionId/TK-421/costumes' => Http::response([
                'legionId' => 'TK-421',
                'memberApproved' => 'YES',
                'memberStanding' => 'Good',
                'memberStatus' => 'Active',
                'joinDate' => '2020-01-15',
                'costumes' => [
                    [
                        'costumeName' => 'ARC Trooper',
                        'photoURL' => 'https://img/lg.jpg',
                        'thumbnail' => 'https://img/sm.jpg',
                        'bucketOffPhoto' => 'https://img/off.jpg',
                    ],
                ],
            ], 200),
        ]);

        $google = Mockery::mock(GoogleService::class);
        $subject = new TheLegionService($organization, $google);

        $subject->run();

        $pivot = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(MembershipStatus::ACTIVE, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
        $this->assertNotNull($pivot->{TrooperOrganization::JOIN_DATE});

        $saved_costume = TrooperCostume::query()
            ->where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::IMAGE_URL_LG, 'https://img/lg.jpg')
            ->first();

        $this->assertNotNull($saved_costume);
        $this->assertSame('https://img/sm.jpg', $saved_costume->{TrooperCostume::IMAGE_URL_SM});
        $this->assertSame('https://img/off.jpg', $saved_costume->{TrooperCostume::IMAGE_URL_BUCKET_OFF});

        $organization->refresh();
        $this->assertNotNull($organization->{Organization::SYNCHRONIZED_AT});
    }

    public function test_run_sets_membership_status_none_when_api_error_present_for_active_member(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create();

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-422',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Http::fake([
            'https://crls.501st.com/costume-reference-library/costumes-by-name' => Http::response(
                '<article><a>AR - ARC Trooper</a></article>',
                200
            ),
            'https://www.501st.com/memberAPI/v3/legionId/TK-422/costumes' => Http::response([
                'legionId' => 'TK-422',
                'error' => 'member not found',
                'costumes' => [],
            ], 200),
        ]);

        $google = Mockery::mock(GoogleService::class);
        $subject = new TheLegionService($organization, $google);

        $subject->run();

        $pivot = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(MembershipStatus::NONE, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
    }

    public function test_run_sets_pivot_retired_and_global_retired_when_api_returns_retired_and_no_other_active_org(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-423',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Http::fake([
            'https://crls.501st.com/costume-reference-library/costumes-by-name' => Http::response(
                '<article><a>AR - ARC Trooper</a></article>',
                200
            ),
            'https://www.501st.com/memberAPI/v3/legionId/TK-423/costumes' => Http::response([
                'legionId' => 'TK-423',
                'memberStatus' => 'Retired',
                'costumes' => [
                    ['costumeName' => 'ARC Trooper', 'photoURL' => null, 'thumbnail' => null, 'bucketOffPhoto' => null],
                ],
            ], 200),
        ]);

        $google = Mockery::mock(GoogleService::class);
        $subject = new TheLegionService($organization, $google);

        $subject->run();

        $pivot = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(MembershipStatus::RETIRED, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
        $this->assertSame(MembershipStatus::RETIRED, $trooper->fresh()->membership_status);
    }

    public function test_run_sets_pivot_retired_but_preserves_global_active_when_other_active_org_exists(): void
    {
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-424',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $other_organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Http::fake([
            'https://crls.501st.com/costume-reference-library/costumes-by-name' => Http::response(
                '<article><a>AR - ARC Trooper</a></article>',
                200
            ),
            'https://www.501st.com/memberAPI/v3/legionId/TK-424/costumes' => Http::response([
                'legionId' => 'TK-424',
                'memberStatus' => 'Retired',
                'costumes' => [
                    ['costumeName' => 'ARC Trooper', 'photoURL' => null, 'thumbnail' => null, 'bucketOffPhoto' => null],
                ],
            ], 200),
        ]);

        $google = Mockery::mock(GoogleService::class);
        $subject = new TheLegionService($organization, $google);

        $subject->run();

        $pivot = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(MembershipStatus::RETIRED, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
        $this->assertSame(MembershipStatus::ACTIVE, $trooper->fresh()->membership_status);
    }

    public function test_run_sets_pivot_retired_and_global_retired_when_api_returns_retired_with_empty_costumes(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-425',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Http::fake([
            'https://crls.501st.com/costume-reference-library/costumes-by-name' => Http::response(
                '<article><a>AR - ARC Trooper</a></article>',
                200
            ),
            'https://www.501st.com/memberAPI/v3/legionId/TK-425/costumes' => Http::response([
                'legionId' => 'TK-425',
                'memberStatus' => 'Retired',
                'costumes' => [],
            ], 200),
        ]);

        $google = Mockery::mock(GoogleService::class);
        $subject = new TheLegionService($organization, $google);

        $subject->run();

        $pivot = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(MembershipStatus::RETIRED, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
        $this->assertSame(MembershipStatus::RETIRED, $trooper->fresh()->membership_status);
    }

    public function test_run_sets_pivot_reserve_when_api_returns_reserve_status(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => 'TK-426',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        Http::fake([
            'https://crls.501st.com/costume-reference-library/costumes-by-name' => Http::response(
                '<article><a>AR - ARC Trooper</a></article>',
                200
            ),
            'https://www.501st.com/memberAPI/v3/legionId/TK-426/costumes' => Http::response([
                'legionId' => 'TK-426',
                'memberApproved' => 'YES',
                'memberStanding' => 'Good',
                'memberStatus' => 'Reserve',
                'costumes' => [],
            ], 200),
        ]);

        $google = Mockery::mock(GoogleService::class);
        $subject = new TheLegionService($organization, $google);

        $subject->run();

        $pivot = TrooperOrganization::query()
            ->where(TrooperOrganization::TROOPER_ID, $trooper->id)
            ->where(TrooperOrganization::ORGANIZATION_ID, $organization->id)
            ->first();

        $this->assertNotNull($pivot);
        $this->assertSame(MembershipStatus::RESERVE, $pivot->{TrooperOrganization::MEMBERSHIP_STATUS});
    }
}
