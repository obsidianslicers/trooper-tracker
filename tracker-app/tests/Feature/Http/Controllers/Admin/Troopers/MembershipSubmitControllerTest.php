<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_memberships_and_redirects_back_to_membership(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $payload = [
            'organizations' => [
                (string) $organization->id => [
                    'identifier' => null,
                    'assignment' => null,
                ],
            ],
        ];

        $response = $this->actingAs($trooper)->post(route('admin.troopers.membership', $subject), $payload);

        $response->assertRedirect(route('admin.troopers.membership', $subject));
    }

    public function test_invoke_updates_organization_membership_status(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();
        TrooperOrganization::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        $payload = [
            'organizations' => [
                (string) $organization->id => [
                    'identifier' => null,
                    'assignment' => null,
                    'membership_status' => MembershipStatus::RETIRED->value,
                ],
            ],
        ];

        $response = $this->actingAs($trooper)->post(route('admin.troopers.membership', $subject), $payload);

        $response->assertRedirect(route('admin.troopers.membership', $subject));
        $this->assertDatabaseHas('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $subject->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
            TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::RETIRED->value,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();

        $response = $this->post(route('admin.troopers.membership', $subject), ['organizations' => []]);

        $response->assertRedirect(route('auth.login'));
    }
}
