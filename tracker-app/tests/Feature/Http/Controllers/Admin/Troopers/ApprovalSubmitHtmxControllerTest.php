<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
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

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->asPending()->create();

        $response = $this->post(route('admin.troopers.approve-htmx', $subject));

        $response->assertRedirect(route('auth.login'));
    }
}
