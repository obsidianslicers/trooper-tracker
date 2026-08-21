<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RedirectPendingTrooperMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_through_unauthenticated_request(): void
    {
        $response = $this->get(route('account.index'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_passes_through_active_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertOk();
    }

    public function test_redirects_pending_trooper_with_setup_completed_to_pending_page(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
            Trooper::SETUP_COMPLETED_AT => now(),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertRedirect(route('account.pending'));
    }

    public function test_passes_through_pending_trooper_without_setup_completed(): void
    {
        $trooper = Trooper::factory()->asPending()->create();

        $response = $this->actingAs($trooper)->get(route('account.setup'));

        $response->assertOk();
    }
}
