<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\GetTrooperMemberships;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperMembershipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_matching_memberships_and_builds_membership_path(): void
    {
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()
            ->asOrganization()
            ->withName('Alpha Club')
            ->withNodePath('100.')
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withName('Beta Region')
            ->withParent($club)
            ->withNodePath('100.200.')
            ->create();

        $unit = Organization::factory()
            ->asUnit()
            ->withName('Gamma Unit')
            ->withParent($region)
            ->withNodePath('100.200.300.')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($club)
            ->withIdentifier('TK-12345')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($unit)
            ->asMember()
            ->create();

        $subject = new GetTrooperMemberships($trooper);

        $result = $subject->handle();

        $this->assertCount(1, $result);
        $this->assertSame($unit->{Organization::ID}, $result->first()->{TrooperAssignment::ORGANIZATION_ID});
        $this->assertSame('Alpha Club - Beta Region - Gamma Unit', $result->first()->membership_path);
        $this->assertNotNull($result->first()->organization_membership);
        $this->assertSame('TK-12345', $result->first()->organization_membership->{TrooperOrganization::IDENTIFIER});
    }

    public function test_handle_filters_out_assignments_without_a_matching_organization_membership(): void
    {
        $trooper = Trooper::factory()->create();

        $club = Organization::factory()
            ->asOrganization()
            ->withName('Alpha Club')
            ->withNodePath('100.')
            ->create();

        $region = Organization::factory()
            ->asRegion()
            ->withName('Beta Region')
            ->withParent($club)
            ->withNodePath('100.200.')
            ->create();

        TrooperAssignment::factory()
            ->forTrooper($trooper)
            ->forOrganization($region)
            ->asMember()
            ->create();

        $subject = new GetTrooperMemberships($trooper);

        $result = $subject->handle();

        $this->assertCount(0, $result);
        $this->assertTrue($result->isEmpty());
    }
}
