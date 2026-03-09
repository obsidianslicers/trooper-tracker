<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_awards_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        Award::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.awards.list'));

        $response->assertOk();
        $response->assertViewIs('pages.admin.awards.list');
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.awards.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
