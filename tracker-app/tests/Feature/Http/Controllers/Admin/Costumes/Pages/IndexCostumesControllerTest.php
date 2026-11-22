<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes\Pages;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexCostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_costumes_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        Costume::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/costumes/List')
            ->has('costumes.data')
            ->has('costumes.meta.links'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.costumes.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
