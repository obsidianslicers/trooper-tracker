<?php

declare(strict_types=1);

namespace App\Messages\Auth\Commands;

use Hyperdrive\Message;
use App\Enums\MembershipStatus;
use App\Facades\TroopTracker;
use App\Models\Trooper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the login command for the application.
 *
 * This command message processes login requests, typically involving authentication
 * via various providers (XenForo OAuth, Google OAuth, email/password).
 *
 * @method static void call(string $email, string $password, bool $remember_me)
 *
 */
final class Login extends Message
{
    public function __construct(
        private readonly string $email,
        private readonly string $password,
        private readonly bool $remember_me)
    {
    }

    /**
     * Handles the login process for the application.
     *
     * @return void
     */
    public function handle(): void
    {
        $trooper = Trooper::query()->byEmail($this->email)->first();

        if ($trooper->membership_status === MembershipStatus::PENDING)
        {
            // $this->flash->warning('Your access has not been approved yet. Please refer to command staff for additional information.');

            // return back()
            //     ->withInput(request()->except('password'))
            //     ->withErrors(['email' => 'Refer to command staff']);
        }

        // if ($trooper->membership_status !== MembershipStatus::ACTIVE)
        // {
        //     //  retired
        //     $this->flash->danger('You cannot access this account. Please refer to command staff for additional information (retired).');

        //     return back()
        //         ->withInput(request()->except('password'))
        //         ->withErrors(['email' => 'You cannot access this account.']);
        // }

        // if (Hash::check($password, $trooper->password))
        // {
        //     Auth::login($trooper, $request->remember_me);

        //     return redirect()->intended(route('events.list'));
        // }

        // return back()
        //     ->withInput(request()->except('password'))
        //     ->withErrors(['email' => 'Invalid email and password.']);
    }
}