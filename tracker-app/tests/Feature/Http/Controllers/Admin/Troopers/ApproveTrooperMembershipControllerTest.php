<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Notifications\Troopers\MembershipApprovedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class ApproveTrooperMembershipControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_approves_pending_trooper_and_returns_membership_page(): void
    {
        Notification::fake();

        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asPending()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.approvals.approve-membership', $trooper));

        $response->assertOk();
        $response->assertInertia(fn($page) => $page
            ->component('admin/troopers/MembershipApprovals')
            ->where('results.message', 'Trooper membership approved successfully.'));

        $this->assertSame(MembershipStatus::ACTIVE, $trooper->fresh()->membership_status);
        Notification::assertSentTo($trooper, MembershipApprovedNotification::class);
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asPending()->create();

        $response = $this->post(route('admin.troopers.approvals.approve-membership', $trooper));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_rejects_a_trooper_who_is_not_pending(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.approvals.approve-membership', $trooper));

        $response->assertRedirect();
        $response->assertSessionHasErrors('trooper');
    }
}