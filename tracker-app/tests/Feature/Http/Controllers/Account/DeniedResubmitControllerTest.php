<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeniedResubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_resubmits_denied_trooper_and_redirects_to_pending(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER,
        ]);

        $org = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [
                $org->id => ['selected' => '1'],
            ],
        ]);

        $response->assertRedirect(route('account.pending'));
        $this->assertEquals(MembershipStatus::PENDING, $trooper->fresh()->membership_status);
    }

    public function test_invoke_resubmits_member_with_identifier(): void
    {
        Queue::fake();

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => '12820',
                    'region_id' => (string) $region->id,
                    'unit_id' => (string) $unit->id,
                ],
            ],
        ]);

        $response->assertRedirect(route('account.pending'));
        $this->assertDatabaseHas('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $unit->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $organization->id,
            TrooperRequest::IDENTIFIER => '12820',
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING->value,
        ]);
    }

    public function test_invoke_rejects_identifier_already_assigned_to_another_trooper(): void
    {
        Queue::fake();

        [$organization, $region, $unit] = $this->createOrganizationHierarchy();

        TrooperRequest::factory()
            ->forTrooper(Trooper::factory()->asMember()->create())
            ->forOrganization($unit)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('12820')
            ->create();

        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::MEMBER,
        ]);

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [
                $organization->id => [
                    'selected' => '1',
                    'identifier' => '12820',
                    'region_id' => (string) $region->id,
                    'unit_id' => (string) $unit->id,
                ],
            ],
        ]);

        $response->assertSessionHasErrors("organizations.{$organization->id}.identifier");
        $this->assertEquals(MembershipStatus::DENIED, $trooper->fresh()->membership_status);
        $this->assertDatabaseMissing('tt_trooper_requests', [
            TrooperRequest::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_invoke_returns_403_if_trooper_is_not_denied(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_invoke_redirects_guest_to_login(): void
    {
        $response = $this->post(route('account.denied.resubmit'));

        $response->assertRedirect(route('auth.login'));
    }

    private function createOrganizationHierarchy(): array
    {
        $organization = Organization::factory()
            ->asOrganization()
            ->withNodePath('100:')
            ->state([
                Organization::IDENTIFIER_DISPLAY => 'TKID',
                Organization::IDENTIFIER_VALIDATION => 'string',
            ])
            ->create();
        $region = Organization::factory()
            ->asRegion()
            ->withParent($organization)
            ->withNodePath('100:200:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();

        return [$organization, $region, $unit];
    }
}
