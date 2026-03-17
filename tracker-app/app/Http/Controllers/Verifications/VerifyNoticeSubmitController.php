<?php

declare(strict_types=1);

namespace App\Http\Controllers\Verifications;

use App\Http\Controllers\MagicBusController;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Handles the email verification process.
 *
 * This controller is responsible for displaying the email verification notice
 * to the user after they have verified their email address.
 */
class VerifyNoticeSubmitController extends MagicBusController
{
    /**
     * Handle the incoming request to display the email verification notice.
     *
     * This method displays a notice to the user after they have verified their email address.
     *
     * @param  Request  $request  The incoming email verification request
     * @return RedirectResponse The response after resending the verification email
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $request->user()->sendEmailVerificationNotification();

        $this->flash->success('A new verification link has been sent to your email.');

        return redirect()->route('verification.notice');
    }
}
