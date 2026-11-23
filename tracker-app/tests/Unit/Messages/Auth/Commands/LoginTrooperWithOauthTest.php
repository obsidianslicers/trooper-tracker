<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Auth\Commands;

use App\Contracts\LoginProvider;
use App\Enums\OauthLoginOutcome;
use App\Messages\Auth\Commands\LoginTrooperWithOauth;
use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class LoginTrooperWithOauthTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_logs_in_trooper_already_linked_to_the_provider_account(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        OauthLogin::factory()->forTrooper($trooper)->forProvider('forum')->create([
            OauthLogin::PROVIDER_ID => 'forum-1',
        ]);
        $provider = $this->provider();
        $provider->shouldReceive('syncTrooper')->once();

        $subject = new LoginTrooperWithOauth($provider, $this->providerUser('forum-1', 'other@example.com'));
        $result = $subject->handle();

        $this->assertSame(OauthLoginOutcome::LOGGED_IN, $result);
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_handle_links_and_logs_in_trooper_found_by_email(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $provider = $this->provider();
        $provider->shouldReceive('syncTrooper')->once();

        $subject = new LoginTrooperWithOauth($provider, $this->providerUser('forum-2', $trooper->email));
        $result = $subject->handle();

        $this->assertSame(OauthLoginOutcome::LOGGED_IN, $result);
        $this->assertAuthenticatedAs($trooper);
        $this->assertDatabaseHas(OauthLogin::class, [
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => 'forum',
            OauthLogin::PROVIDER_ID => 'forum-2',
            OauthLogin::TOKEN => 'token',
        ]);
    }

    public function test_handle_logs_in_denied_trooper_so_they_can_see_why(): void
    {
        $trooper = Trooper::factory()->asDenied()->create();
        $provider = $this->provider();
        $provider->shouldNotReceive('syncTrooper');

        $subject = new LoginTrooperWithOauth($provider, $this->providerUser('forum-3', $trooper->email));
        $result = $subject->handle();

        $this->assertSame(OauthLoginOutcome::DENIED, $result);
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_handle_does_not_log_in_trooper_who_cannot_log_in(): void
    {
        $trooper = Trooper::factory()->asRetired()->create();
        $provider = $this->provider();
        $provider->shouldNotReceive('syncTrooper');

        $subject = new LoginTrooperWithOauth($provider, $this->providerUser('forum-4', $trooper->email));
        $result = $subject->handle();

        $this->assertSame(OauthLoginOutcome::INACTIVE, $result);
        $this->assertGuest();
        $this->assertDatabaseMissing(OauthLogin::class, [OauthLogin::PROVIDER_ID => 'forum-4']);
    }

    public function test_handle_keeps_provider_details_for_registration_when_no_trooper_matches(): void
    {
        $subject = new LoginTrooperWithOauth($this->provider(), $this->providerUser('forum-5', 'new@example.com'));
        $result = $subject->handle();

        $this->assertSame(OauthLoginOutcome::REGISTER, $result);
        $this->assertGuest();
        $this->assertSame('forum', Session::get('registration_auth.method'));
        $this->assertSame('new@example.com', Session::get('registration_auth.email'));
        $this->assertSame('forum-5', Session::get('oauth_pending.provider_id'));
        $this->assertSame('token', Session::get('oauth_pending.token'));
    }

    public function test_handle_reports_missing_email(): void
    {
        Auth::shouldReceive('login')->never();

        $subject = new LoginTrooperWithOauth($this->provider(), $this->providerUser('forum-6', null));
        $result = $subject->handle();

        $this->assertSame(OauthLoginOutcome::MISSING_EMAIL, $result);
        $this->assertFalse(Session::has('registration_auth'));
    }

    private function provider(): LoginProvider&MockInterface
    {
        $provider = Mockery::mock(LoginProvider::class);
        $provider->shouldReceive('key')->andReturn('forum');

        return $provider;
    }

    private function providerUser(string $id, ?string $email): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw([])
            ->map(['id' => $id, 'email' => $email, 'name' => 'Provider Name'])
            ->setToken('token');
    }
}
