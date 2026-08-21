<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PendingControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_pending_page_for_pending_trooper(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.pending'));

        $response->assertOk();
        $response->assertViewIs('pages.account.pending');
        $response->assertViewHas('trooper', $trooper);
    }

    public function test_invoke_redirects_active_trooper_to_profile(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.pending'));

        $response->assertRedirect(route('account.index'));
    }

    public function test_invoke_redirects_guest_to_login(): void
    {
        $response = $this->get(route('account.pending'));

        $response->assertRedirect(route('auth.login'));
    }
}
