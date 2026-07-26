<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the merge troopers page for admin users.
 *
 * This controller handles the merge troopers page request, automatically redirecting
 * already authenticated troopers to the home page to prevent redundant logins.
 */
class MergeTroopersController extends Controller
{
    /**
     * Handle the incoming request to display the merge troopers view.
     *
     * If the trooper is already authenticated, redirects them to the home page.
     * Otherwise, displays the merge troopers form with available actions.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|SymfonyResponse The merge troopers page view or redirect to home if authenticated
     */
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        return Inertia::render('admin/troopers/MergeTroopers', []);
    }
}
