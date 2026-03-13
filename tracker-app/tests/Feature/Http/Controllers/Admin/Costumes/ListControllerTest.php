<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_costumes_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Costume::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.costumes.list');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.costumes.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
