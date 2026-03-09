<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChangesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_trooper_changes_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $subject = Trooper::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.changes', $subject));

        $response->assertOk();
        $response->assertViewIs('pages.admin.troopers.changes');
    }

    public function test_invoke_requires_authentication(): void
    {
        $subject = Trooper::factory()->create();

        $response = $this->get(route('admin.troopers.changes', $subject));

        $response->assertRedirect(route('auth.login'));
    }
}
