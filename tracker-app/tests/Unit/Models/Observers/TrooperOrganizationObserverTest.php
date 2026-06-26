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

    public function test_saved_demotes_global_to_retired_when_last_active_pivot_changes_to_retired(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $pivot->membership_status = MembershipStatus::RETIRED;
        $pivot->save();

        $this->assertSame(MembershipStatus::RETIRED, $trooper->fresh()->membership_status);
    }

    public function test_saved_preserves_global_active_when_pivot_changes_to_retired_but_another_active_org_remains(): void
    {
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $other_organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $pivot->membership_status = MembershipStatus::RETIRED;
        $pivot->save();

        $this->assertSame(MembershipStatus::ACTIVE, $trooper->fresh()->membership_status);
    }

    public function test_saved_promotes_global_to_active_when_retired_trooper_gets_active_org(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asRetired()->create();

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED,
        ]);

        $pivot->membership_status = MembershipStatus::ACTIVE;
        $pivot->save();

        $this->assertSame(MembershipStatus::ACTIVE, $trooper->fresh()->membership_status);
    }

    public function test_saved_does_not_reconcile_when_membership_status_unchanged(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        // Change only identifier — membership_status wasChanged() returns false
        $pivot->identifier = 'TK-999';
        $pivot->save();

        $this->assertSame(MembershipStatus::ACTIVE, $trooper->fresh()->membership_status);
    }

    public function test_saved_does_not_touch_invalid_global_status(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::INVALID,
        ]);

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
        ]);

        $pivot->membership_status = MembershipStatus::RETIRED;
        $pivot->save();

        $this->assertSame(MembershipStatus::INVALID, $trooper->fresh()->membership_status);
    }

    public function test_saved_does_not_promote_pending_trooper_to_active(): void
    {
        $organization = Organization::factory()->create();
        $trooper = Trooper::factory()->asPending()->create();

        $pivot = TrooperOrganization::factory()->create([
            TrooperOrganization::TROOPER_ID => $trooper->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED,
        ]);

        $pivot->membership_status = MembershipStatus::ACTIVE;
        $pivot->save();

        $this->assertSame(MembershipStatus::PENDING, $trooper->fresh()->membership_status);
    }
}
