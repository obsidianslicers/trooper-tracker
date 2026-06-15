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

class ApprovalSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_approves_trooper_and_returns_approval_card(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->asPending()->create();

        $response = $this->actingAs($trooper)->post(route('admin.troopers.approve-htmx', $subject));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.approval-card');
        $response->assertHeader('X-Flash-Message');

        $subject->refresh();

        $this->assertSame(MembershipStatus::ACTIVE->value, $subject->{Trooper::MEMBERSHIP_STATUS}->value);
    }

    public function test_invoke_returns_simple_approved_state_after_approval(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->asPending()->create();
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withNodePath('100:')
            ->create();
        $region = Organization::factory()
            ->asRegion()
            ->withName('Florida Garrison')
            ->withParent($organization)
            ->withNodePath('100:200:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withName('Makaze Squad')
            ->withParent($region)
            ->withNodePath('100:200:300:')
            ->create();

        TrooperRequest::factory()
            ->forTrooper($subject)
            ->forOrganization($unit)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('545345')
            ->create();

        $response = $this->actingAs($trooper)->post(route('admin.troopers.approve-htmx', $subject));

        $response->assertOk();
        $response->assertSeeText('Review complete.');
        $response->assertSeeText('Approved');
        $response->assertSeeText('Trooper account is active.');
        $response->assertDontSeeText('Primary Club:');
        $response->assertDontSeeText('Assigned Unit:');
        $response->assertDontSeeText('Identifier:');
        $response->assertDontSeeText('No organization membership was requested.');
    }

    public function test_invoke_returns_error_when_pending_request_identifier_is_already_assigned(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $existing_member = Trooper::factory()->asMember()->create();
        $subject = Trooper::factory()->asPending()->create();
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withIdentifierDisplay('TKID')
            ->withNodePath('100:')
            ->create();
        $unit = Organization::factory()
            ->asUnit()
            ->withName('Makaze Squad')
            ->withParent($organization)
            ->withNodePath('100:200:')
            ->create();

        TrooperOrganization::factory()
            ->forTrooper($existing_member)
            ->forOrganization($organization)
            ->withIdentifier('1012')
            ->create([TrooperOrganization::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE]);

        TrooperRequest::factory()
            ->forTrooper($subject)
            ->forOrganization($unit)
            ->forPrimaryOrganization($organization)
            ->withIdentifier('1012')
            ->create();

        $response = $this->actingAs($trooper)->post(route('admin.troopers.approve-htmx', $subject));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.approval-card');

        $flash = json_decode((string) $response->headers->get('X-Flash-Message'), true);
        $this->assertSame('danger', $flash['type']);
        $this->assertSame('501st Legion TKID 1012 is already assigned to another trooper.', $flash['message']);

        $subject->refresh();
        $this->assertSame(MembershipStatus::PENDING->value, $subject->{Trooper::MEMBERSHIP_STATUS}->value);
        $this->assertDatabaseMissing('tt_trooper_organizations', [
            TrooperOrganization::TROOPER_ID => $subject->id,
            TrooperOrganization::ORGANIZATION_ID => $organization->id,
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->asPending()->create();

        $response = $this->post(route('admin.troopers.approve-htmx', $subject));

        $response->assertRedirect(route('auth.login'));
    }
}
