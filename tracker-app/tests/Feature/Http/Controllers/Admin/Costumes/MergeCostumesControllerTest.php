<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class MergeCostumesControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_merge_costumes_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.merge'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('admin/costumes/MergeCostumes'));
    }

    public function test_invoke_forbids_moderators(): void
    {
        $trooper = Trooper::factory()->asModerator()->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.merge'));

        $this->assertContains($response->status(), [302, 403]);
        $this->assertFalse($response->isOk());
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.costumes.merge'));

        $response->assertRedirect(route('auth.login'));
    }
}
