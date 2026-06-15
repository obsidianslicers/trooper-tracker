<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\TrooperRequest;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\TrooperRequestApproveHtmxController
 */
class TrooperRequestApproveHtmxControllerTest extends TestCase
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

        $response = $this->post(route('admin.troopers.trooper-requests.approve-htmx', $trooper_request));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_approves_join_request_and_returns_card_fragment(): void
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
            ->post(route('admin.troopers.trooper-requests.approve-htmx', $trooper_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.trooper-request-card');
        $this->assertNotEmpty($response->headers->get('X-Flash-Message'));
    }

    public function test_invoke_returns_error_when_request_identifier_is_already_assigned(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $existing_member = Trooper::factory()->asMember()->create();
        $member = Trooper::factory()->asMember()->create();
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->withNodePath('100:')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_member)
            ->forOrganization($organization)
            ->withIdentifier('1012')
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        $trooper_request = TrooperRequest::withoutEvents(fn() => TrooperRequest::factory()
            ->forTrooper($member)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('1012')
            ->create());

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.trooper-requests.approve-htmx', $trooper_request));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.trooper-request-card');

        $flash = json_decode((string) $response->headers->get('X-Flash-Message'), true);
        $this->assertSame('danger', $flash['type']);
        $this->assertSame('501st Legion TKID 1012 is already assigned to another trooper.', $flash['message']);
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
            ->post(route('admin.troopers.trooper-requests.approve-htmx', $trooper_request));

        $response->assertForbidden();
    }
}
