<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Enums\MembershipStatus;
use App\Messages\Troopers\Queries\GetTrooperCostumes;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperCostumesTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_organization_names_returns_inactive_when_no_active_organization_matches(): void
    {
        $trooper = Trooper::factory()->create();
        $subject = new GetTrooperCostumes($trooper);
        $costume = new Costume([
            Costume::NAME => 'Alpha Boots',
        ]);
        $costume->setRelation('organization_costumes', collect());

        $method = new \ReflectionMethod(GetTrooperCostumes::class, 'getOrganizationNames');
        $method->setAccessible(true);

        $this->assertSame('(inactive)', $method->invoke($subject, $costume));
    }

    public function test_handle_returns_costumes_for_active_organizations_and_calculates_metadata(): void
    {
        $trooper = Trooper::factory()->create();
        $alpha_org = Organization::factory()->create([Organization::NAME => 'Alpha Org']);
        $beta_org = Organization::factory()->create([Organization::NAME => 'Beta Org']);
        $other_org = Organization::factory()->create([Organization::NAME => 'Other Org']);

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($alpha_org)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($beta_org)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($other_org)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::PENDING]);

        $costume = Costume::factory()->create([Costume::NAME => 'Alpha Boots']);
        $alpha_org_costume = OrganizationCostume::factory()
            ->forCostume($costume)
            ->forOrganization($alpha_org)
            ->create();
        $beta_org_costume = OrganizationCostume::factory()
            ->forCostume($costume)
            ->forOrganization($beta_org)
            ->create();

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($alpha_org_costume)
            ->create([
                TrooperCostume::IMAGE_URL_SM => 'https://example.com/sm.png',
                TrooperCostume::IMAGE_URL_LG => 'https://example.com/lg.png',
                TrooperCostume::IMAGE_URL_BUCKET_OFF => 'https://example.com/off.png',
            ]);

        TrooperCostume::factory()
            ->forTrooper($trooper)
            ->forOrganizationCostume($beta_org_costume)
            ->create([
                TrooperCostume::IMAGE_URL_SM => 'https://example.com/alt-sm.png',
                TrooperCostume::IMAGE_URL_LG => null,
                TrooperCostume::IMAGE_URL_BUCKET_OFF => null,
            ]);

        $command_staff = Costume::factory()->create([Costume::NAME => Costume::COMMAND_STAFF]);
        $handler = Costume::factory()->create([Costume::NAME => Costume::HANDLER]);

        $subject = new GetTrooperCostumes($trooper);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame('Alpha Boots', $result->first()->{Costume::NAME});
        $this->assertSame('(*) Alpha Org, Beta Org', $result->first()->costume_organizations);
        $this->assertSame(
            [
                'https://example.com/sm.png',
                'https://example.com/lg.png',
                'https://example.com/off.png',
                'https://example.com/alt-sm.png',
            ],
            $result->first()->image_urls,
        );
        $this->assertFalse($result->contains(fn(Costume $candidate): bool => in_array($candidate->{Costume::NAME}, [Costume::COMMAND_STAFF, Costume::HANDLER], true)));
        $this->assertFalse($result->contains(fn(Costume $candidate): bool => $candidate->is($command_staff) || $candidate->is($handler)));
    }
}
