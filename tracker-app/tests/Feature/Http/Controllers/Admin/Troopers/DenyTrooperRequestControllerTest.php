<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperRequestDeniedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class DenyTrooperRequestControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_denies_pending_trooper_request_and_returns_membership_page(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper_request = TrooperRequest::factory()->asPending()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.approvals.deny-request', $trooper_request), [
                'denial_reason' => 'Not eligible',
            ]);

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('admin/troopers/MembershipApprovals'));

        $this->assertSame('denied', $trooper_request->fresh()->status->value);
        $this->assertSame('Not eligible', $trooper_request->fresh()->denial_reason);
        Notification::assertSentTo($trooper_request->trooper, TrooperRequestDeniedNotification::class);
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper_request = TrooperRequest::factory()->asPending()->create();

        $response = $this->post(route('admin.troopers.approvals.deny-request', $trooper_request), [
            'denial_reason' => 'Not eligible',
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_rejects_a_trooper_request_that_is_not_pending(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper_request = TrooperRequest::factory()->asApproved()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.approvals.deny-request', $trooper_request), [
                'denial_reason' => 'Not eligible',
            ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('trooper_request');
    }
}
