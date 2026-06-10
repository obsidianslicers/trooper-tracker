<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\JoinRequestStatus;
use App\Enums\MembershipStatus;
use App\Models\JoinRequest;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenialSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_denies_trooper_and_returns_approval_card(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->asPending()->create();
        $organization = Organization::factory()
            ->asOrganization()
            ->withName('501st Legion')
            ->withNodePath('100:')
            ->create();

        $join_request = JoinRequest::factory()
            ->forTrooper($subject)
            ->forOrganization($organization)
            ->forPrimaryOrganization($organization)
            ->create();

        $response = $this->actingAs($trooper)->post(route('admin.troopers.deny-htmx', $subject), [
            'denial_reason' => 'Not eligible',
        ]);

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.approval-card');
        $response->assertHeader('X-Flash-Message');
        $response->assertSeeText('Trooper denied.');
        $response->assertDontSeeText('Primary Club:');
        $response->assertDontSeeText('No organization membership was requested.');

        $subject->refresh();
        $join_request->refresh();

        $this->assertSame(MembershipStatus::DENIED->value, $subject->{Trooper::MEMBERSHIP_STATUS}->value);
        $this->assertEquals(JoinRequestStatus::DENIED, $join_request->status);
        $this->assertSame('Not eligible', $join_request->denial_reason);
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->asPending()->create();

        $response = $this->post(route('admin.troopers.deny-htmx', $subject));

        $response->assertRedirect(route('auth.login'));
    }
}
