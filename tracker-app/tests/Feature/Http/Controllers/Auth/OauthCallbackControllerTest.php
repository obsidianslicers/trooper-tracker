<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;
use Mockery;
use Tests\TestCase;

class OauthCallbackControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_authenticates_trooper_with_existing_oauth_account(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $oauth_login = OauthLogin::factory()
            ->forTrooper($trooper)
            ->forProvider('google')
            ->create();

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn($oauth_login->provider_id);
        $socialite_user->shouldReceive('getEmail')->andReturn($trooper->email);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_invoke_redirects_inactive_trooper_to_inactive_page(): void
    {
        $trooper = Trooper::factory()->asRetired()->create();

        $oauth_login = OauthLogin::factory()
            ->forTrooper($trooper)
            ->forProvider('google')
            ->create();

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn($oauth_login->provider_id);
        $socialite_user->shouldReceive('getEmail')->andReturn($trooper->email);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        $response->assertRedirect(route('auth.inactive'));
        $this->assertGuest();
    }

    public function test_invoke_initiates_registration_for_new_oauth_user(): void
    {
        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-123');
        $socialite_user->shouldReceive('getEmail')->andReturn('newuser@example.com');
        $socialite_user->shouldReceive('getName')->andReturn('New User');
        $socialite_user->shouldReceive('token')->andReturn('oauth-token');
        $socialite_user->shouldReceive('refreshToken')->andReturn(null);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        $response->assertRedirect(route('auth.register'));
        $this->assertNotNull(Session::get('registration_auth'));
        $this->assertNotNull(Session::get('oauth_pending'));
    }

    public function test_invoke_authenticates_existing_trooper_by_email_and_links_oauth(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-789');
        $socialite_user->shouldReceive('getEmail')->andReturn($trooper->email);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        $response->assertRedirect('/');
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_invoke_redirects_to_login_on_invalid_state(): void
    {
        Socialite::shouldReceive('driver')
            ->with('xenforo')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andThrow(new InvalidStateException);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'xenforo']));

        $response->assertRedirect(route('auth.login'));
        $this->assertGuest();
    }

    public function test_invoke_rejects_oauth_without_email(): void
    {
        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('xenforo-456');
        $socialite_user->shouldReceive('getEmail')->andReturn(null);
        $socialite_user->shouldReceive('getRaw')->andReturn([]);

        Socialite::shouldReceive('driver')
            ->with('xenforo')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'xenforo']));

        $response->assertRedirect();
        $response->assertSessionHasErrors(['oauth']);
    }

    public function test_invoke_logs_in_denied_trooper_via_linked_oauth_and_redirects_to_denied(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $oauth_login = OauthLogin::factory()
            ->forTrooper($trooper)
            ->forProvider('google')
            ->create();

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn($oauth_login->provider_id);
        $socialite_user->shouldReceive('getEmail')->andReturn($trooper->email);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        $response->assertRedirect(route('account.denied'));
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_invoke_logs_in_denied_trooper_via_email_match_and_redirects_to_denied(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();

        $socialite_user = Mockery::mock(SocialiteUser::class);
        $socialite_user->shouldReceive('getId')->andReturn('google-new-id');
        $socialite_user->shouldReceive('getEmail')->andReturn($trooper->email);

        Socialite::shouldReceive('driver')
            ->with('google')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($socialite_user);

        $response = $this->get(route('auth.oauth-callback', ['provider' => 'google']));

        $response->assertRedirect(route('account.denied'));
        $this->assertAuthenticatedAs($trooper);
    }
}
