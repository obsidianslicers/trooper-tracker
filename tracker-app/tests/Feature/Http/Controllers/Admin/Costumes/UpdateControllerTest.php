<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Costumes;

use App\Models\Costume;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_update_costume_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();
        $costume = Costume::factory()->create();

        $response = $this->actingAs($trooper)->get(route('admin.costumes.update', ['costume' => $costume->id]));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/costumes/Update')
            ->where('costume.id', $costume->id)
            ->where('confirm_delete', false)
            ->has('organizations')
            ->has('deletion_impact'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $costume = Costume::factory()->create();

        $response = $this->get(route('admin.costumes.update', ['costume' => $costume->id]));

        $response->assertRedirect(route('auth.login'));
    }
}
