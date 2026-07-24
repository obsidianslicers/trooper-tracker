<?php

declare(strict_types=1);

namespace App\Messages\Auth\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the login command for the application.
 *
 * This command message processes login requests, typically involving authentication
 * via various providers (XenForo OAuth, Google OAuth, email/password).
 *
 * @method static Trooper|null call(string $email, string $password, bool $remember_me)
 */
final class Login extends Message
{
    public function __construct(
        private readonly string $email,
        private readonly string $password,
        private readonly bool $remember_me) {}

    /**
     * Handles the login process for the application.
     */
    public function handle(): ?Trooper
    {
        $trooper = Trooper::query()->byEmail($this->email)->first();

        if ($trooper)
        {
            if (Hash::check($this->password, $trooper->password))
            {
                Auth::login($trooper, $this->remember_me);

                return $trooper;
            }
        }

        return null;
    }
}
