<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\OauthLogin;
use App\Models\Trooper;
use App\Models\TrooperApiCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MobileApiControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_forum_returns_mobile_api_key_for_linked_active_trooper(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        $trooper = Trooper::factory()->asActive()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => 'xenforo',
            OauthLogin::PROVIDER_ID => '12345',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response([
                'me' => [
                    'user_id' => 12345,
                    'username' => 'TK-12345',
                    'avatar_urls' => ['s' => 'https://img.test/avatar-sm.png'],
                ],
            ], 200),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'login' => '12345',
            'access_token' => 'forum-access-token',
        ]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('user.user_id', '12345');
        $response->assertJsonPath('user.username', 'TK-12345');

        $api_key = $response->json('apiKey');

        $this->assertIsString($api_key);
        $this->assertSame(64, strlen($api_key));

        $this->assertDatabaseHas('tt_trooper_api_codes', [
            TrooperApiCode::TROOPER_ID => $trooper->id,
            TrooperApiCode::API_CODE => $api_key,
        ]);

        $this->assertDatabaseHas('tt_oauth_logins', [
            OauthLogin::TROOPER_ID => $trooper->id,
            OauthLogin::PROVIDER => 'xenforo',
            OauthLogin::PROVIDER_ID => '12345',
            OauthLogin::TOKEN => 'forum-access-token',
        ]);
    }

    public function test_login_with_forum_returns_unauthorized_when_forum_token_is_invalid(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response(['error' => 'Unauthorized'], 401),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'login' => '12345',
            'access_token' => 'bad-token',
        ]);

        $response->assertStatus(401);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Unable to authenticate with the forum.');
    }

    public function test_login_with_forum_returns_not_found_for_unlinked_forum_account(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response([
                'me' => [
                    'user_id' => 99999,
                    'username' => 'Unlinked Trooper',
                ],
            ], 200),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'login' => '99999',
            'access_token' => 'forum-access-token',
        ]);

        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath(
            'error',
            'No Troop Tracker account is linked to this forum account.',
        );
    }

    public function test_login_with_forum_rejects_inactive_trooper(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.me_path' => '/api/me',
        ]);

        $trooper = Trooper::factory()->asRetired()->create();

        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => 'xenforo',
            OauthLogin::PROVIDER_ID => '12345',
        ]);

        Http::fake([
            'https://xf.test/api/me' => Http::response([
                'me' => [
                    'user_id' => 12345,
                    'username' => 'Retired Trooper',
                ],
            ], 200),
        ]);

        $response = $this->post(route('api.mobile'), [
            'action' => 'login_with_forum',
            'login' => '12345',
            'access_token' => 'forum-access-token',
        ]);

        $response->assertStatus(403);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('error', 'Trooper account is inactive.');
    }
}