<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorRenewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_view_for_authenticated_visitor(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $response = $this->actingAs($trooper)->get(route('account.visitor-renew'));

        $response->assertOk();
        $response->assertViewIs('pages.account.visitor-renew');
        $response->assertViewHas('trooper', $trooper);
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('account.visitor-renew'));

        $response->assertRedirect(route('auth.login'));
    }
}
