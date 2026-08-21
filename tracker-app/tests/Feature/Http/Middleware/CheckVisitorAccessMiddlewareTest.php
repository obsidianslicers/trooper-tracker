<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckVisitorAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_through_unauthenticated_request(): void
    {
        $response = $this->get(route('account.index'));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_passes_through_non_visitor_trooper(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertOk();
    }

    public function test_passes_through_visitor_with_unexpired_access(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->addMonths(3),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertOk();
    }

    public function test_redirects_expired_active_visitor(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.index'));

        $response->assertRedirect(route('account.visitor-renew'));
    }

    public function test_passes_through_expired_visitor_on_renewal_route(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.visitor-renew'));

        $response->assertOk();
    }

    public function test_passes_through_expired_visitor_on_auth_route(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $response = $this->actingAs($trooper)->get(route('auth.logout'));

        $response->assertRedirect(route('auth.login', ['logged_out' => '1']));
    }

    public function test_passes_through_expired_visitor_on_verification_route(): void
    {
        $trooper = Trooper::factory()->asActive()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $response = $this->actingAs($trooper)->get(route('verification.notice'));

        $response->assertOk();
        $response->assertViewIs('pages.verifications.notice');
    }
}
