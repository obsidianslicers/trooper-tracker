<?php

declare(strict_types=1);

namespace App\Http\Controllers\Verifications;

use App\Http\Controllers\MagicBusController;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Handles the email verification process.
 *
 * This controller is responsible for displaying the email verification notice
 * to the user after they have verified their email address.
 */
class VerifyNoticeController extends MagicBusController
{
    /**
     * Handle the incoming request to display the email verification notice.
     *
     * This method displays a notice to the user after they have verified their email address.
     *
     * @param  Request  $request  The incoming email verification request
     * @return View The view after displaying the verification notice
     */
    public function __invoke(Request $request): View
    {
        return view('pages.verifications.notice');
    }
}
