<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Trooper;
use App\Plugins\PluginLoginProviders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fixtures\Auth\ForumLoginProvider;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_displays_home_page_for_guests(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertViewIs('pages.home');
    }

    public function test_invoke_shows_a_login_button_to_the_login_page(): void
    {
        $this->get(route('home'))
            ->assertViewHas('login_url', route('auth.login'))
            ->assertSee('data-test="home-login"', false)
            ->assertSee(route('auth.login'), false);
    }

    public function test_invoke_sends_the_login_button_straight_to_a_required_provider(): void
    {
        ForumLoginProvider::$required = true;
        $this->app->make(PluginLoginProviders::class)->add(ForumLoginProvider::class);

        $this->get(route('home'))
            ->assertViewHas('login_url', route('auth.oauth-redirect', ['provider' => 'forum']))
            ->assertSee('Login with Florida Garrison');
    }

    public function test_invoke_redirects_authenticated_troopers_to_events_list(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('home'));

        $response->assertRedirect(route('events.list'));
    }

    protected function tearDown(): void
    {
        ForumLoginProvider::reset();

        parent::tearDown();
    }
}
