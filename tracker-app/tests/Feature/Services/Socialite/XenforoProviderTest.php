<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Socialite;

use App\Services\Socialite\XenforoProvider;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Laravel\Socialite\Two\User;
use Tests\TestCase;

class XenforoProviderTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_auth_url_builds_authorize_url_with_state_and_scope(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.authorize_path' => '/index.php?oauth/authorize',
            'services.xenforo.token_path' => '/index.php?oauth/token',
            'services.xenforo.me_path' => '/api/me',
        ]);

        $subject = new TestableXenforoProvider(
            Request::create('/'),
            'client-id',
            'client-secret',
            'https://app.test/callback'
        );

        $url = $subject->publicGetAuthUrl('abc123');

        $this->assertStringContainsString('https://xf.test/index.php?oauth/authorize', $url);
        $this->assertStringContainsString('state=abc123', $url);
        $this->assertStringContainsString('scope=user%3Aread%20user%3Awrite', $url);
    }

    public function test_get_token_url_uses_configured_token_path(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.token_path' => '/oauth/token',
        ]);

        $subject = new TestableXenforoProvider(
            Request::create('/'),
            'id',
            'secret',
            'https://app.test/callback'
        );

        $this->assertSame('https://xf.test/oauth/token', $subject->publicGetTokenUrl());
    }

    public function test_map_user_to_object_prefers_original_avatar_then_large_then_medium(): void
    {
        $subject = new TestableXenforoProvider(
            Request::create('/'),
            'id',
            'secret',
            'https://app.test/callback'
        );

        $user = $subject->publicMapUserToObject([
            'me' => [
                'user_id' => 25,
                'username' => 'TK-421',
                'email' => 'tk421@example.test',
                'avatar_urls' => [
                    'o' => 'https://img/original.jpg',
                    'l' => 'https://img/large.jpg',
                    'm' => 'https://img/medium.jpg',
                ],
            ],
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame(25, $user->id);
        $this->assertSame('TK-421', $user->nickname);
        $this->assertSame('https://img/original.jpg', $user->avatar);
    }

    public function test_get_token_fields_includes_authorization_code_grant_type(): void
    {
        $subject = new TestableXenforoProvider(
            Request::create('/'),
            'id',
            'secret',
            'https://app.test/callback'
        );

        $fields = $subject->publicGetTokenFields('code-1');

        $this->assertSame('authorization_code', $fields['grant_type']);
        $this->assertSame('code-1', $fields['code']);
    }
}

class TestableXenforoProvider extends XenforoProvider
{
    public function publicGetAuthUrl(string $state): string
    {
        return $this->getAuthUrl($state);
    }

    public function publicGetTokenUrl(): string
    {
        return $this->getTokenUrl();
    }

    /**
     * @param  array<string, mixed>  $user
     */
    public function publicMapUserToObject(array $user): User
    {
        return $this->mapUserToObject($user);
    }

    /**
     * @return array<string, string>
     */
    public function publicGetTokenFields(string $code): array
    {
        return $this->getTokenFields($code);
    }
}
