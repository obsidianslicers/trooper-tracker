<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDisplayControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_admin_dashboard_for_admin_trooper(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.display'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.display');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.display'));

        $response->assertRedirect(route('auth.login'));
    }
}
