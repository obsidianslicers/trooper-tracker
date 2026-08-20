<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\TrooperMembership;

use App\Messages\Troopers\Queries\TrooperMembership\IsOrganizationIdentifierAvailable;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IsOrganizationIdentifierAvailableTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_true_when_identifier_is_empty(): void
    {
        $primary_organization = Organization::factory()->asOrganization()->create();

        $subject = new IsOrganizationIdentifierAvailable($primary_organization, null);

        $result = $subject->handle();

        $this->assertTrue($result);
    }

    public function test_handle_returns_false_when_identifier_exists_on_membership(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new IsOrganizationIdentifierAvailable($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertFalse($result);
    }

    public function test_handle_returns_false_when_identifier_exists_on_pending_request(): void
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

        $subject = new IsOrganizationIdentifierAvailable($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertFalse($result);
    }

    public function test_handle_returns_true_when_identifier_has_no_membership_or_request_conflict(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-99999')
            ->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-88888')
            ->asPending()
            ->create();

        $subject = new IsOrganizationIdentifierAvailable($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertTrue($result);
    }

    public function test_handle_returns_true_when_only_matching_records_are_ignored(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()->asRegion()->withParent($primary_organization)->create();

        $trooper_organization = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->asPending()
            ->create();

        $subject = new IsOrganizationIdentifierAvailable(
            $primary_organization,
            'TK-12345',
            $trooper->id,
            $trooper_request->id,
            $trooper_organization->id,
        );

        $result = $subject->handle();

        $this->assertTrue($result);
    }
}