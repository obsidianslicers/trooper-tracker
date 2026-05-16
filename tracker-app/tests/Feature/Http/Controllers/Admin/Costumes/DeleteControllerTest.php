<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeleteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_confirmation_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.delete', compact('costume')));

        $response->assertOk();
        $response->assertViewIs('pages.admin.costumes.delete');
    }

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->get(route('admin.costumes.delete', compact('costume')));

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_prevents_deletion_of_handler_costume(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->withName(Costume::HANDLER)->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.delete', compact('costume')));

        $response->assertForbidden();
    }

    public function test_invoke_prevents_deletion_of_command_staff_costume(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $costume = Costume::factory()->withName(Costume::COMMAND_STAFF)->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.delete', compact('costume')));

        $response->assertForbidden();
    }
}
