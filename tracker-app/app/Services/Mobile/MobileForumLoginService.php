<?php

declare(strict_types=1);

namespace App\Services\Mobile;

use App\Models\OauthLogin;
use App\Models\Trooper;
use Illuminate\Support\Facades\Http;

class MobileForumLoginService
{
    /**
     * @return array{trooper: Trooper, forum_user: array<string, mixed>}
     */
    public function authenticate(string $forum_user_id, string $access_token): array
    {
        if ($forum_user_id === '' || $access_token === '') {
            throw new MobileForumLoginException('Forum login and access token are required.', 400);
        }

        $forum_user = $this->fetchForumUser($access_token);
        $resolved_user_id = (string) ($forum_user['user_id'] ?? '');

        if ($resolved_user_id === '' || $resolved_user_id !== $forum_user_id) {
            throw new MobileForumLoginException(
                'Forum login does not match the authenticated forum account.',
                403,
            );
        }

        $oauth_login = OauthLogin::where(OauthLogin::PROVIDER, 'xenforo')
            ->where(OauthLogin::PROVIDER_ID, $resolved_user_id)
            ->with('trooper')
            ->first();

        if (!$oauth_login?->trooper) {
            throw new MobileForumLoginException(
                'No Troop Tracker account is linked to this forum account.',
                404,
            );
        }

        if (!$oauth_login->trooper->is_active) {
            throw new MobileForumLoginException('Trooper account is inactive.', 403);
        }

        $oauth_login->update([OauthLogin::TOKEN => $access_token]);

        return [
            'trooper' => $oauth_login->trooper,
            'forum_user' => $forum_user,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchForumUser(string $access_token): array
    {
        $response = Http::acceptJson()
            ->withToken($access_token)
            ->get($this->forumMeUrl());

        if (!$response->successful()) {
            throw new MobileForumLoginException('Unable to authenticate with the forum.', 401);
        }

        $forum_user = $response->json('me');

        if (!is_array($forum_user)) {
            throw new MobileForumLoginException('Forum user profile could not be loaded.', 502);
        }

        return $forum_user;
    }

    private function forumMeUrl(): string
    {
        $base_url = rtrim((string) config('services.xenforo.base_url'), '/');
        $me_path = '/'.ltrim((string) config('services.xenforo.me_path', '/api/me'), '/');

        return $base_url.$me_path;
    }
}