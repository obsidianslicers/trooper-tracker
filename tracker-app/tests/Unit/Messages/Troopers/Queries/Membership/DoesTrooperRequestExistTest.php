<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\Membership;

use App\Enums\TrooperRequestStatus;
use App\Messages\Troopers\Queries\Membership\DoesTrooperRequestExist;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoesTrooperRequestExistTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_true_when_matching_pending_request_exists(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->asPending()
            ->create();

        $subject = new DoesTrooperRequestExist($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertTrue($result);
    }

    public function test_handle_returns_false_when_status_does_not_match(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->asDenied()
            ->create();

        $subject = new DoesTrooperRequestExist($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertFalse($result);
    }

    public function test_handle_can_match_non_default_status_when_provided(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->asDenied()
            ->create();

        $subject = new DoesTrooperRequestExist(
            $primary_organization,
            'TK-12345',
            null,
            null,
            TrooperRequestStatus::DENIED,
        );

        $result = $subject->handle();

        $this->assertTrue($result);
    }

    public function test_handle_ignores_matching_request_for_given_trooper_id(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->asPending()
            ->create();

        $subject = new DoesTrooperRequestExist(
            $primary_organization,
            'TK-12345',
            $trooper->id,
        );

        $result = $subject->handle();

        $this->assertFalse($result);
    }

    public function test_handle_ignores_matching_request_for_given_request_id(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->asPending()
            ->create();

        $subject = new DoesTrooperRequestExist(
            $primary_organization,
            'TK-12345',
            null,
            $trooper_request->id,
        );

        $result = $subject->handle();

        $this->assertFalse($result);
    }
}