<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Troopers\Queries\TrooperMembership;

use App\Messages\Troopers\Queries\TrooperMembership\DoesTrooperOrganizationExist;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoesTrooperOrganizationExistTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_returns_true_when_matching_membership_exists(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new DoesTrooperOrganizationExist($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertTrue($result);
    }

    public function test_handle_returns_false_when_matching_membership_does_not_exist(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-99999')
            ->create();

        $subject = new DoesTrooperOrganizationExist($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertFalse($result);
    }

    public function test_handle_includes_soft_deleted_memberships(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        $trooper_organization = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $trooper_organization->delete();

        $subject = new DoesTrooperOrganizationExist($primary_organization, 'TK-12345');

        $result = $subject->handle();

        $this->assertTrue($result);
    }

    public function test_handle_ignores_matching_membership_for_given_trooper_id(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new DoesTrooperOrganizationExist(
            $primary_organization,
            'TK-12345',
            $trooper->id,
        );

        $result = $subject->handle();

        $this->assertFalse($result);
    }

    public function test_handle_ignores_matching_membership_for_given_membership_id(): void
    {
        $trooper = Trooper::factory()->asMember()->create();
        $primary_organization = Organization::factory()->asOrganization()->create();

        $trooper_organization = TrooperOrganization::factory()
            ->forTrooper($trooper)
            ->forOrganization($primary_organization)
            ->withIdentifier('TK-12345')
            ->create();

        $subject = new DoesTrooperOrganizationExist(
            $primary_organization,
            'TK-12345',
            null,
            $trooper_organization->id,
        );

        $result = $subject->handle();

        $this->assertFalse($result);
    }
}