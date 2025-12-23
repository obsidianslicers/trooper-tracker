<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\FlashMessageService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the thank you page after registration.
 */
class ThankYouController extends Controller
{
    /**
     * Handle the incoming request to display the thank you page.
     *
     * @param Request $request The incoming HTTP request.
     * @return RedirectResponse A redirect response to the login page.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.auth.thank-you');
    }
}
