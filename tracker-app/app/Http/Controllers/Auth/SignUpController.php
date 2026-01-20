<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the sign-up method selection page.
 *
 * This controller renders the initial sign-up page where troopers can
 * choose between different registration methods (Email, Google OAuth,
 * XenForo OAuth) before proceeding to the registration form.
 */
class SignUpController extends MagicBusController
{
    /**
     * Handle the incoming request to display the sign-up method selection page.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered sign-up page view showing registration method options.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.auth.signup');
    }
}
