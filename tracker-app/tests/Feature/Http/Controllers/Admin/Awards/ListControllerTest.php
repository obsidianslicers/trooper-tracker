<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards;

use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_awards_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        Award::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.awards.list'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/awards/List')
            ->has('awards.data', 2)
            ->where('awards.data.0.can_update', true)
            ->where('awards.data.0.troopers_count', '0')
            ->has('awards.meta.links'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.awards.list'));

        $response->assertRedirect(route('auth.login'));
    }
}
