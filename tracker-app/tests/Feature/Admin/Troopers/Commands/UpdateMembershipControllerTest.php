<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Troopers\Commands;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_membership_and_returns_results(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $payload = ['identifier' => null, 'assignment' => null];

        $response = $this->actingAs($trooper)->post(
            route('admin.troopers.update-membership', [$subject, $organization]),
            $payload
        );

        $response->assertOk();
    }

    public function test_invoke_updates_organization_membership_status(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperOrganization::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        $payload = [
            'identifier' => null,
            'assignment' => null,
            'membership_status' => MembershipStatus::RETIRED->value,
        ];

        $response = $this->actingAs($trooper)->post(
            route('admin.troopers.update-membership', [$subject, $organization]),
            $payload
        );

        $response->assertOk();
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $subject->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
    }

    public function test_invoke_rejects_invalid_membership_status(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $response = $this->actingAs($trooper)->post(
            route('admin.troopers.update-membership', [$subject, $organization]),
            ['membership_status' => 'invalid-status']
        );

        $response->assertSessionHasErrors('membership_status');
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $response = $this->post(
            route('admin.troopers.update-membership', [$subject, $organization]),
            []
        );

        $response->assertRedirect(route('auth.login'));
    }
}
