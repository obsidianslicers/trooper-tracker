<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Xenforo;

use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Plugins\PluginLoginProviders;
use App\Services\Forums\XenforoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Auth\XenforoLoginProvider;

class XenforoLoginTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.xenforo.name' => 'The Forum',
            'services.xenforo.client_id' => 'client-id',
            'services.xenforo.client_secret' => 'secret',
            'services.xenforo.redirect' => 'https://example.com/callback',
            'services.google.client_id' => 'google-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect' => 'https://example.com/google/callback',
        ]);
        $this->enableXenforo();
    }

    public function test_plugin_adds_its_login_provider(): void
    {
        $subject = $this->app->make(PluginLoginProviders::class);

        $this->assertSame(['google', 'xenforo'], $this->keys($subject->available()));
        $this->assertSame('The Forum', $subject->find('xenforo')?->name());
    }

    public function test_required_forum_login_is_the_only_one_available(): void
    {
        config(['tracker.auth.require_xenforo' => true]);

        $subject = $this->app->make(PluginLoginProviders::class);

        $this->assertSame(['xenforo'], $this->keys($subject->available()));
        $this->assertSame(
            'Troop Tracker is configured to use The Forum for login.',
            $subject->unavailableReason('google'),
        );
    }

    public function test_callback_refuses_google_while_the_forum_is_required(): void
    {
        config(['tracker.auth.require_xenforo' => true]);
        Socialite::shouldReceive('driver')->never();

        $this->get(route('auth.oauth-callback', ['provider' => 'google']))
            ->assertRedirect(route('auth.login'));
    }

    public function test_email_sign_up_is_off_while_the_forum_is_required(): void
    {
        config(['tracker.auth.require_xenforo' => true]);

        $this->get(route('auth.signup-email'))
            ->assertRedirect(route('auth.signup'))
            ->assertSessionHas('warning');
        $this->assertNull(session('registration_auth'));
    }

    public function test_registration_needs_the_required_forum_login(): void
    {
        config(['tracker.auth.require_xenforo' => true]);
        session(['registration_auth' => [
            'method' => 'google',
            'email' => 'a@example.com',
            'expires_at' => now()->addMinutes(20),
        ]]);

        $this->get(route('auth.register'))
            ->assertRedirect(route('auth.signup'))
            ->assertSessionHas('error', 'Please sign up with The Forum.');
    }

    public function test_callback_refuses_a_banned_forum_user(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        OauthLogin::factory()->forTrooper($trooper)->forProvider('xenforo')->create([
            OauthLogin::PROVIDER_ID => '77',
        ]);

        $this->fakeForumUser('77', $trooper->email, ['me' => ['is_banned' => true]]);

        $this->get(route('auth.oauth-callback', ['provider' => 'xenforo']))
            ->assertRedirect(route('auth.login'))
            ->assertSessionHas('danger');
        $this->assertGuest();
    }

    public function test_refusal_asks_the_forum_api_when_me_has_no_ban_field(): void
    {
        $xenforo = Mockery::mock(XenforoService::class);
        $xenforo->shouldReceive('get_user')->once()->with(77)
            ->andReturn(['status' => 200, 'body' => ['user' => ['is_banned' => true]]]);

        $subject = new XenforoLoginProvider($xenforo);

        $this->assertNotNull($subject->refusal($this->forumUser('77', 'a@example.com', [])));
    }

    public function test_callback_syncs_the_trooper_email_from_the_forum(): void
    {
        $trooper = Trooper::factory()->asActive()->create([Trooper::EMAIL_VERIFIED_AT => null]);
        OauthLogin::factory()->forTrooper($trooper)->forProvider('xenforo')->create([
            OauthLogin::PROVIDER_ID => '77',
        ]);

        $this->fakeForumUser('77', 'forum@example.com', ['me' => ['is_banned' => false]]);

        $this->get(route('auth.oauth-callback', ['provider' => 'xenforo']))->assertRedirect('/');

        $trooper->refresh();
        $this->assertSame('forum@example.com', $trooper->email);
        $this->assertNotNull($trooper->email_verified_at);
        $this->assertAuthenticatedAs($trooper);
    }

    public function test_callback_sends_a_forum_user_without_email_to_the_link_page(): void
    {
        $this->fakeForumUser('77', null, ['me' => ['is_banned' => false]]);

        $this->get(route('auth.oauth-callback', ['provider' => 'xenforo']))
            ->assertRedirect(route('account.xenforo.required'))
            ->assertSessionHasErrors('oauth');
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function fakeForumUser(string $id, ?string $email, array $raw): void
    {
        Socialite::shouldReceive('driver')
            ->with('xenforo')
            ->andReturn(Mockery::self())
            ->shouldReceive('user')
            ->andReturn($this->forumUser($id, $email, $raw));
    }

    /**
     * @param  array<string, mixed>  $raw
     */
    private function forumUser(string $id, ?string $email, array $raw): SocialiteUser
    {
        return (new SocialiteUser)->setRaw($raw)->map(['id' => $id, 'email' => $email, 'name' => 'Forum User']);
    }

    /**
     * @param  array<int, \App\Contracts\LoginProvider>  $providers
     * @return array<int, string>
     */
    private function keys(array $providers): array
    {
        return array_map(fn ($provider): string => $provider->key(), $providers);
    }
}
