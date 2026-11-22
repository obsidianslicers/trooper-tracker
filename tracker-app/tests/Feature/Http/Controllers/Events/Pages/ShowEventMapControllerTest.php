<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Events\Pages;

use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ShowEventMapControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_renders_the_svelte_map(): void
    {
        $trooper = Trooper::factory()->asActive()->withVerifiedEmail()->create();

        $response = $this->actingAs($trooper)->get(route('events.map'));

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('events/Map')
            ->has('events')
            ->has('hosting_organizations')
            ->has('has_map'));
    }

    public function test_invoke_requires_authentication(): void
    {
        $response = $this->get(route('events.map'));

        $response->assertRedirect(route('auth.login'));
    }
}
