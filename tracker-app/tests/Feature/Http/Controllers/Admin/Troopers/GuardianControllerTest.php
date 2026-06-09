<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardianControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_guardian_view_for_admin(): void
    {
        $admin = Trooper::factory()->asAdministrator()->create();
        $trooper = Trooper::factory()->create();

        $response = $this->actingAs($admin)->get(route('admin.troopers.guardian', $trooper));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.guardian');
    }

    public function test_invoke_requires_authentication(): void
    {
        $trooper = Trooper::factory()->create();

        $response = $this->get(route('admin.troopers.guardian', $trooper));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbids_member_role(): void
    {
        $member = Trooper::factory()->asMember()->create();
        $trooper = Trooper::factory()->create();

        $response = $this->actingAs($member)->get(route('admin.troopers.guardian', $trooper));

        $response->assertForbidden();
    }
}
