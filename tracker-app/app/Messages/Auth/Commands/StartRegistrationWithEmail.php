<?php

declare(strict_types=1);

namespace App\Messages\Auth\Commands;

use App\Contracts\LoginProvider;
use App\Facades\TroopTracker;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Session;

/**
 * Starts registering with an email and password, for the next 20 minutes.
 *
 * When everyone must sign up with a login provider, nothing starts and that provider is returned.
 *
 * @method static LoginProvider|null call()
 */
final class StartRegistrationWithEmail extends Message
{
    public function handle(TroopTracker $troop_tracker): ?LoginProvider
    {
        $required = $troop_tracker->requiredLoginProvider();

        if ($required !== null)
        {
            Session::forget('registration_auth');

            return $required;
        }

        Session::put('registration_auth', [
            'method' => 'email',
            'email' => null, // will be filled in on the form
            'expires_at' => now()->addMinutes(20),
        ]);

        return null;
    }
}
