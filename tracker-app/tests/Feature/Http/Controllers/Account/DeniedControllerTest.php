<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeniedControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_denied_page_for_denied_trooper(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertOk();
        $response->assertViewIs('pages.account.denied');
        $response->assertViewHas('trooper', $trooper);
    }

    public function test_invoke_displays_denial_reason_from_trooper_request(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        TrooperRequest::factory()
            ->forTrooper($trooper)
            ->asDenied(reason: 'Costume does not meet standards.')
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertOk();
        $response->assertViewHas('denial_reason', 'Costume does not meet standards.');
    }

    public function test_invoke_redirects_active_trooper_to_profile(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertRedirect(route('account.index'));
    }

    public function test_invoke_redirects_pending_trooper_to_pending(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.denied'));

        $response->assertRedirect(route('account.pending'));
    }

    public function test_invoke_redirects_guest_to_login(): void
    {
        $response = $this->get(route('account.denied'));

        $response->assertRedirect(route('auth.login'));
    }
}
