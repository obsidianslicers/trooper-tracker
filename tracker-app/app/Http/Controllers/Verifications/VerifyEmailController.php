<?php

declare(strict_types=1);

namespace App\Http\Controllers\Verifications;

use App\Http\Controllers\MagicBusController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

/**
 * Handles the email verification process.
 *
 * This controller is responsible for verifying the user's email address
 * and redirecting them to the appropriate page after verification.
 */
class VerifyEmailController extends MagicBusController
{
    /**
     * Handle the incoming request to verify the user's email address.
     *
     * This method fulfills the email verification request and redirects
     * the user to the account profiles page.
     *
     * @param  EmailVerificationRequest  $request  The incoming email verification request
     * @return RedirectResponse The response after redirecting the user
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        $this->flash->success('Thank you for verifying your email!');

        return redirect()->route('account.index');
    }
}
