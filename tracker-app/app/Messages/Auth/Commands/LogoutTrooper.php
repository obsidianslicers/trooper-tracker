<?php

declare(strict_types=1);

namespace App\Messages\Auth\Commands;

use Hyperdrive\Message;
use Illuminate\Support\Facades\Auth;

/**
 * Logs the signed-in trooper out.
 *
 * @method static void call()
 */
final class LogoutTrooper extends Message
{
    public function handle(): void
    {
        Auth::logout();
    }
}
