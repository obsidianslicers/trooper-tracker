<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Awards\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CreateAwardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_create_award_page_for_admin(): void
    {
        $trooper = Trooper::factory()->asSystemOwner()->create();

        $response = $this->actingAs($trooper)->get(route('admin.awards.create'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('admin/awards/Create')
            ->where('award.id', null)
            ->where('award.frequency', 'once')
            ->has('frequency_options', 5)
            ->has('organization_options'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('admin.awards.create'));

        $response->assertRedirect(route('auth.login'));
    }
}
