<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\TrooperRequestStatus;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\TrooperRequestDenyHtmxController
 */
class TrooperRequestDenyHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $member = Trooper::factory()->asMember()->create();
        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->post(route('admin.troopers.trooper-requests.deny-htmx', $trooper_request));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_denies_join_request_and_returns_card_fragment(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.trooper-requests.deny-htmx', $trooper_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.trooper-request-card');
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));

        $trooper_request->refresh();
        $this->assertEquals(TrooperRequestStatus::DENIED, $trooper_request->status);
    }

    public function test_invoke_requires_moderate_authorization(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $other_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $trooper_request = TrooperRequest::factory()
            ->forTrooper($other_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($member)
            ->post(route('admin.troopers.trooper-requests.deny-htmx', $trooper_request));

        $response->assertForbidden();
    }
}
