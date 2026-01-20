<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the inactive account page.
 */
class InactiveController extends MagicBusController
{
    /**
     * Handle the incoming request to display the inactive account page.
     *
     * @param Request $request The incoming HTTP request.
     * @return View A view response displaying the inactive account page.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.auth.inactive');
    }
}
