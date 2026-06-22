<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DeniedResubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_resubmits_denied_trooper_and_redirects_to_pending(): void
    {
        Queue::fake();

        $trooper = Trooper::factory()->asDenied()->create([
            Trooper::MEMBERSHIP_ROLE => MembershipRole::HANDLER,
        ]);

        $org = Organization::factory()->asOrganization()->create();

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [
                $org->id => ['selected' => '1'],
            ],
        ]);

        $response->assertRedirect(route('account.pending'));
        $this->assertEquals(MembershipStatus::PENDING, $trooper->fresh()->membership_status);
    }

    public function test_invoke_returns_403_if_trooper_is_not_denied(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->post(route('account.denied.resubmit'), [
            'organizations' => [],
        ]);

        $response->assertStatus(403);
    }

    public function test_invoke_redirects_guest_to_login(): void
    {
        $response = $this->post(route('account.denied.resubmit'));

        $response->assertRedirect(route('auth.login'));
    }
}
