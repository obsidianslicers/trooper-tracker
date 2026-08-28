<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries;

use App\Messages\Troopers\Queries\FindExistingTrooperMembership;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FindExistingTrooperMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_trooper_from_existing_request(): void
    {
        $primary_organization = Organization::factory()->asOrganization()->create();
        $organization = Organization::factory()
            ->asRegion()
            ->withParent($primary_organization)
            ->create();

        $existing_trooper = Trooper::factory()->asMember()->create();

        TrooperRequest::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($organization)
            ->forPrimaryOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new FindExistingTrooperMembership(
            'TK-12345',
            $primary_organization,
            Trooper::factory()->asMember()->create(),
        );

        $result = $subject->handle();

        $this->assertNotNull($result);
        $this->assertSame($existing_trooper->id, $result->id);
    }

    public function test_handle_returns_trooper_from_existing_organization_membership(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $existing_trooper = Trooper::factory()->asMember()->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new FindExistingTrooperMembership(
            'TK-12345',
            $organization,
            Trooper::factory()->asMember()->create(),
        );

        $result = $subject->handle();

        $this->assertNotNull($result);
        $this->assertSame($existing_trooper->id, $result->id);
    }

    public function test_handle_ignores_membership_for_given_trooper(): void
    {
        $organization = Organization::factory()->asOrganization()->create();
        $ignored_trooper = Trooper::factory()->asMember()->create();

        TrooperOrganization::factory()
            ->forTrooper($ignored_trooper)
            ->forOrganization($organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new FindExistingTrooperMembership(
            'TK-12345',
            $organization,
            $ignored_trooper,
        );

        $result = $subject->handle();

        $this->assertNull($result);
    }

    public function test_handle_returns_null_when_no_matching_membership_exists(): void
    {
        $organization = Organization::factory()->asOrganization()->create();

        $subject = new FindExistingTrooperMembership(
            'TK-12345',
            $organization,
            Trooper::factory()->asMember()->create(),
        );

        $result = $subject->handle();

        $this->assertNull($result);
    }
}
