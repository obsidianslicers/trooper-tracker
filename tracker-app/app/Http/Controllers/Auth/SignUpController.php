<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Messages\Auth\PageData\SignUpPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the sign-up method selection page.
 *
 * This controller renders the initial sign-up page where troopers can
 * choose between different registration methods (Email, Google OAuth,
 * XenForo OAuth) before proceeding to the registration form.
 */
class SignUpController extends Controller
{
    /**
     * Handle the incoming request to display the sign-up method selection page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|SymfonyResponse The rendered sign-up page view showing registration method options
     */
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = SignUpPageData::call();

        return Inertia::render('auth/SignUp', $data);
    }
}
