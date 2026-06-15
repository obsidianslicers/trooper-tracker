<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Enums\MembershipStatus;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Models\Observers\TrooperOrganizationObserver;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see TrooperOrganizationObserver
 */
class TrooperOrganizationObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_throws_when_identifier_belongs_to_another_trooper(): void
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->withNodePath('100:')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper(Trooper::factory()->create())
            ->forOrganization($organization)
            ->withIdentifier('1012')
            ->create();

        $trooper_organization = new TrooperOrganization([
            TrooperOrganization::TROOPER_ID => Trooper::factory()->create()->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::IDENTIFIER => '1012',
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
        $trooper_organization->setRelation('organization', $organization);

        $this->expectException(DuplicateOrganizationIdentifierException::class);
        $this->expectExceptionMessage('501st Legion TKID 1012 is already assigned to another trooper.');

        (new TrooperOrganizationObserver())->saving($trooper_organization);
    }

    public function test_saving_allows_existing_identifier_for_same_trooper_organization(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->create();

        $trooper_organization = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->withIdentifier('1012')
            ->create();

        $trooper_organization->setRelation('organization', $organization);

        $this->expectNotToPerformAssertions();
        (new TrooperOrganizationObserver())->saving($trooper_organization);
    }

    public function test_saving_still_requires_primary_club_membership(): void
    {
        $primary = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($primary)
            ->withNodePath('100:200:')
            ->create();

        $trooper_organization = new TrooperOrganization([
            TrooperOrganization::TROOPER_ID => Trooper::factory()->create()->id,
            TrooperOrganization::ORGANIZATION_ID => $unit->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);
        $trooper_organization->setRelation('organization', $unit);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Trooper can only be a member at top-level organizations.');

        (new TrooperOrganizationObserver())->saving($trooper_organization);
    }
}
