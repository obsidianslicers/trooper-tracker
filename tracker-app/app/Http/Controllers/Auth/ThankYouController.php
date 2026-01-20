<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the thank you page after registration.
 */
class ThankYouController extends MagicBusController
{
    /**
     * Handle the incoming request to display the thank you page.
     *
     * @param Request $request The incoming HTTP request.
     * @return View A view response displaying the thank you page.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.auth.thank-you');
    }
}
