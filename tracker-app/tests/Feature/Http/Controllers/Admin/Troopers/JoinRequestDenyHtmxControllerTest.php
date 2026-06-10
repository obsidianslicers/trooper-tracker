<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\JoinRequestStatus;
use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\JoinRequestDenyHtmxController
 */
class JoinRequestDenyHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $member = Trooper::factory()->asMember()->create();
        $join_request = JoinRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->post(route('admin.troopers.join-requests.deny-htmx', $join_request));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_denies_join_request_and_returns_card_fragment(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.join-requests.deny-htmx', $join_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.join-request-card');
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));

        $join_request->refresh();
        $this->assertEquals(JoinRequestStatus::DENIED, $join_request->status);
    }

    public function test_invoke_requires_moderate_authorization(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $other_member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($other_member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($member)
            ->post(route('admin.troopers.join-requests.deny-htmx', $join_request));

        $response->assertForbidden();
    }
}
