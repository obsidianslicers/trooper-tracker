<?php

declare(strict_types=1);

namespace App\Services\Socialite;

use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

/**
 * XenForo OAuth2 Provider for Laravel Socialite.
 *
 * Implements OAuth2 authentication for XenForo 2.3+ forums.
 * Supports both confidential and public client types with PKCE.
 *
 * @see https://xenforo.com/community/threads/have-you-seen-single-sign-on-and-more-with-oauth2-in-xenforo-2-3.218304/
 */
class XenforoProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * The scopes being requested.
     *
     * @var array<int, string>
     */
    protected $scopes = ['read', 'profile'];

    /**
     * The separating character for the requested scopes.
     *
     * @var string
     */
    protected $scopeSeparator = ' ';

    /**
     * Get the base URL for the XenForo instance.
     *
     * @return string
     */
    protected function getBaseUrl(): string
    {
        return rtrim(config('services.xenforo.base_url'), '/');
    }

    /**
     * Get the authentication URL for the provider.
     *
     * @param string $state
     * @return string
     */
    protected function getAuthUrl($state): string
    {
        return $this->buildAuthUrlFromBase(
            $this->getBaseUrl() . '/oauth/authorize',
            $state
        );
    }

    /**
     * Get the token URL for the provider.
     *
     * @return string
     */
    protected function getTokenUrl(): string
    {
        return $this->getBaseUrl() . '/oauth/token';
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param string $token
     * @return array<string, mixed>
     */
    protected function getUserByToken($token): array
    {
        $response = $this->getHttpClient()->get(
            $this->getBaseUrl() . '/api/me',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * Map the raw user array to a Socialite User instance.
     *
     * @param array<string, mixed> $user
     * @return \Laravel\Socialite\Two\User
     */
    protected function mapUserToObject(array $user): User
    {
        $user_data = $user['user'] ?? $user;

        return (new User())->setRaw($user)->map([
            'id' => $user_data['user_id'] ?? null,
            'nickname' => $user_data['username'] ?? null,
            'name' => $user_data['username'] ?? null,
            'email' => $user_data['email'] ?? null,
            'avatar' => $this->getAvatarUrl($user_data),
        ]);
    }

    /**
     * Get the avatar URL for the user.
     *
     * @param array<string, mixed> $user_data
     * @return string|null
     */
    protected function getAvatarUrl(array $user_data): ?string
    {
        if (isset($user_data['avatar_urls']['o']))
        {
            return $user_data['avatar_urls']['o'];
        }

        if (isset($user_data['avatar_urls']['l']))
        {
            return $user_data['avatar_urls']['l'];
        }

        if (isset($user_data['avatar_urls']['m']))
        {
            return $user_data['avatar_urls']['m'];
        }

        return null;
    }

    /**
     * Get the POST fields for the token request.
     *
     * @param string $code
     * @return array<string, string>
     */
    protected function getTokenFields($code): array
    {
        return array_merge(parent::getTokenFields($code), [
            'grant_type' => 'authorization_code',
        ]);
    }
}
