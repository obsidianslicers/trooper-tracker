<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\Admin\Troopers\MarkRipSubmitController
 */
class MarkRipSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->post(route('admin.troopers.rip', $trooper));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_requires_administrator(): void
    {
        $actor = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($actor)
            ->post(route('admin.troopers.rip', $trooper));

        $response->assertForbidden();
    }

    public function test_invoke_marks_trooper_rip_and_redirects_to_profile(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.rip', $trooper));

        $response->assertRedirect(route('admin.troopers.profile', $trooper));
        $trooper->refresh();
        $this->assertEquals(MembershipStatus::RIP, $trooper->membership_status);
    }

    public function test_invoke_denies_when_trooper_is_already_rip(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create(['membership_status' => MembershipStatus::RIP]);

        $response = $this->actingAs($admin)
            ->post(route('admin.troopers.rip', $trooper));

        $response->assertForbidden();
    }
}
