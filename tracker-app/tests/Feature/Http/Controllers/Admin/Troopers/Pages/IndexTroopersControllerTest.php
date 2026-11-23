<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class IndexTroopersControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_troopers_list_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        Trooper::factory()->count(2)->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.index'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/troopers/List')
            ->has('troopers.data', 3)
            ->where('filters.sort_col', 'display_name')
            ->where('filters.sort_dir', 'asc'));
    }

    public function test_invoke_keeps_the_filters_and_ignores_an_unknown_sort(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.troopers.index', [
            'membership_role' => 'moderator',
            'sort_col' => 'password',
            'sort_dir' => 'desc',
        ]));

        $response->assertInertia(fn (Assert $page) => $page
            ->where('filters.membership_role', 'moderator')
            ->where('filters.sort_col', 'display_name')
            ->where('filters.sort_dir', 'desc')
            ->has('troopers.data', 0));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.troopers.index'));

        $response->assertRedirect(route('auth.login'));
    }
}
