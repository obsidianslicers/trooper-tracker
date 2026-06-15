<?php

declare(strict_types=1);

namespace Tests\Unit\Models\Observers;

use App\Enums\TrooperRequestStatus;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Models\TrooperRequest;
use App\Models\Observers\TrooperRequestObserver;
use App\Models\Organization;
use App\Models\Trooper;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see TrooperRequestObserver
 */
class TrooperRequestObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_allows_status_transition_from_pending(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $trooper_request->status = TrooperRequestStatus::APPROVED;

        $subject = new TrooperRequestObserver();

        $this->expectNotToPerformAssertions();
        $subject->updating($trooper_request);
    }

    public function test_updating_throws_when_transitioning_from_approved(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $trooper_request->status = TrooperRequestStatus::DENIED;

        $subject = new TrooperRequestObserver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Join request is not pending.');
        $subject->updating($trooper_request);
    }

    public function test_updating_throws_when_transitioning_from_denied(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asDenied()
            ->create();

        $trooper_request->status = TrooperRequestStatus::APPROVED;

        $subject = new TrooperRequestObserver();

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Join request is not pending.');
        $subject->updating($trooper_request);
    }

    public function test_updating_skips_check_when_status_is_not_dirty(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->asApproved()
            ->create();

        $trooper_request->denial_reason = 'updated reason';

        $subject = new TrooperRequestObserver();

        $this->expectNotToPerformAssertions();
        $subject->updating($trooper_request);
    }

    public function test_saving_throws_when_pending_identifier_belongs_to_another_pending_request(): void
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->withNodePath('100:')
            ->create();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->create())
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('1012')
            ->create();

        $trooper_request = new TrooperRequest([
            TrooperRequest::TROOPER_ID => Trooper::factory()->create()->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => '1012',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
        $trooper_request->setRelation('primaryOrganization', $organization);

        $this->expectException(DuplicateOrganizationIdentifierException::class);
        $this->expectExceptionMessage('501st Legion TKID 1012 is already assigned to another trooper.');

        (new TrooperRequestObserver())->saving($trooper_request);
    }

    public function test_saving_allows_pending_identifier_for_same_trooper(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $trooper = Trooper::factory()->asMember()->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('1012')
            ->create();

        $trooper_request = new TrooperRequest([
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => '1012',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);
        $trooper_request->setRelation('primaryOrganization', $organization);

        $this->expectNotToPerformAssertions();
        (new TrooperRequestObserver())->saving($trooper_request);
    }
}
