<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorRenewSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_sets_membership_status_to_pending(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $this->actingAs($trooper)->post(route('account.visitor-renew-submit'));

        $this->assertDatabaseHas($trooper->getTable(), [
            Trooper::ID               => $trooper->id,
            Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING->value,
        ]);
    }

    public function test_invoke_redirects_to_thank_you(): void
    {
        $trooper = Trooper::factory()->create([
            Trooper::MEMBERSHIP_ROLE    => MembershipRole::VISITOR,
            Trooper::MEMBERSHIP_STATUS  => MembershipStatus::ACTIVE,
            Trooper::VISITOR_EXPIRES_AT => now()->subMonth(),
        ]);

        $response = $this->actingAs($trooper)->post(route('account.visitor-renew-submit'));

        $response->assertRedirect(route('auth.thank-you'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->post(route('account.visitor-renew-submit'));

        $response->assertRedirect(route('auth.login'));
    }
}
