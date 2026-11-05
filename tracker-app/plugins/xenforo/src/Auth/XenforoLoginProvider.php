<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Auth;

use App\Contracts\LoginProvider;
use App\Models\Trooper;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

/**
 * Sign in with a XenForo forum account. When the forum is required, it is
 * the only way to sign in.
 */
final class XenforoLoginProvider implements LoginProvider
{
    public const KEY = 'xenforo';

    public function __construct(private readonly XenforoService $xenforo) {}

    public function key(): string
    {
        return self::KEY;
    }

    public function name(): string
    {
        return (string) config('plugins.xenforo.name') ?: 'XenForo';
    }

    public function iconUrl(): string
    {
        return 'https://xenforo.com/community/styles/default/xenforo/xenforo-favicon.png';
    }

    public function isConfigured(): bool
    {
        return filled(config('plugins.xenforo.client_id'))
            && filled(config('plugins.xenforo.client_secret'))
            && filled(config('plugins.xenforo.redirect'));
    }

    public function isRequired(): bool
    {
        return (bool) config('plugins.xenforo.require_xenforo', false);
    }

    public function refusal(SocialiteUser $user): ?string
    {
        if (!$this->isBanned($user))
        {
            return null;
        }

        return 'You are currently banned. Please refer to command staff for additional information.';
    }

    /**
     * Keep the trooper's email and verification in step with the forum account.
     */
    public function syncTrooper(Trooper $trooper, SocialiteUser $user): void
    {
        $email = $user->getEmail();

        if (!empty($email) && $trooper->email !== $email)
        {
            $trooper->email = $email;
        }

        if ($trooper->email_verified_at === null)
        {
            $trooper->email_verified_at = now();
        }

        if ($trooper->isDirty(['email', 'email_verified_at']))
        {
            $trooper->save();
        }
    }

    public function missingEmailRoute(): string
    {
        return 'account.xenforo.required';
    }

    private function isBanned(SocialiteUser $user): bool
    {
        $raw = method_exists($user, 'getRaw') ? $user->getRaw() : [];
        $me = $raw['me'] ?? $raw;

        // Fall back to the admin API if the /me endpoint didn't include the field.
        if (array_key_exists('is_banned', $me))
        {
            return !empty($me['is_banned']);
        }

        $result = $this->xenforo->get_user((int) $user->getId());

        return $result['status'] === 200 && !empty($result['body']['user']['is_banned']);
    }
}
