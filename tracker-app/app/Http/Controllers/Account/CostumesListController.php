<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the notification settings form via an HTMX request.
 */
class CostumesListController extends Controller
{
    /**
     * Handle the incoming request to display the notification settings.
     *
     * @param Request $request The incoming HTTP request.
     * @return View The rendered notification settings view.
     */
    public function __invoke(Request $request): View
    {
        return view('pages.account.costumes', []);
    }
}
