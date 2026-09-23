<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\ApproveMembershipsController
 */
class ApproveMembershipsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_membership_approvals_page_for_moderator(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($moderator)->get(route('admin.troopers.approvals.index'));

        $response->assertOk();
        $response->assertInertia(fn(Assert $page) => $page->component('admin/troopers/MembershipApprovals'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.troopers.approvals.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
