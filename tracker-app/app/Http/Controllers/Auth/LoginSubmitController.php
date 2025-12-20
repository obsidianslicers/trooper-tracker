<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Enums\MembershipStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Trooper;
use App\Services\FlashMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Handles the submission of the login form, authenticates the user, and manages the session.
 */
class LoginSubmitController extends Controller
{
    /**
     * @param FlashMessageService $flash The flash message service.
     */
    public function __construct(private readonly FlashMessageService $flash)
    {
    }

    /**
     * Handles the incoming login request.
     *
     * @param LoginRequest $request The validated login form request.
     * @return RedirectResponse A redirect response to the intended page or back with errors.
     */
    public function __invoke(LoginRequest $request): RedirectResponse
    {
        $username = $request->validated('username');
        $password = $request->validated('password');

        //  trooper existance is checked via LoginRequest
        $trooper = Trooper::query()->byUsername($username)->first();

        if ($trooper->membership_status == MembershipStatus::PENDING)
        {
            $this->flash->warning('Your access has not been approved yet. Please refer to command staff for additional information.');

            return back()
                ->withInput(request()->except('password'))
                ->withErrors(['username' => 'Refer to command staff']);
        }

        if ($trooper->membership_status != MembershipStatus::ACTIVE)
        {
            //  retired
            $this->flash->danger('You cannot access this account. Please refer to command staff for additional information (retired).');

            return back()
                ->withInput(request()->except('password'))
                ->withErrors(['username' => 'You cannot access this account.']);
        }

        if (Hash::check($password, $trooper->password))
        {
            Auth::login($trooper, $request->remember_me);

            return redirect()->intended(route('events.list'));
        }

        return back()
            ->withInput(request()->except('password'))
            ->withErrors(['username' => 'Invalid username and password.']);
    }
}
