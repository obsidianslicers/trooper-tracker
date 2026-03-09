<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_memberships_and_redirects_back_to_membership(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->create();
        $organization = Organization::factory()->create();

        $payload = [
            'organizations' => [
                (string) $organization->id => [
                    'identifier' => null,
                    'assignment' => null,
                ],
            ],
        ];

        $response = $this->actingAs($trooper)->post(route('admin.troopers.membership', $subject), $payload);

        $response->assertRedirect(route('admin.troopers.membership', $subject));
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();

        $response = $this->post(route('admin.troopers.membership', $subject), ['organizations' => []]);

        $response->assertRedirect(route('auth.login'));
    }
}
