<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the authenticated trooper's profile management page.
 *
 * This controller follows the ADR pattern as an Action that:
 * - Retrieves the authenticated trooper from the request
 * - Passes trooper data to the profile view
 * - Renders the account profile page where troopers can view and manage
 *   their personal information, contact details, and profile settings
 */
class ProfileController extends Controller
{
    /**
     * Handle the incoming request to display the profile page.
     *
     * Workflow:
     * 1. Retrieves the authenticated trooper from the request
     * 2. Prepares view data with trooper instance
     * 3. Renders the profile management page
     *
     * The profile page allows troopers to view and edit their:
     * - Personal information (name, email, phone)
     * - Profile preferences and settings
     * - Account details and status
     *
     * @param Request $request The incoming HTTP request containing the authenticated trooper
     * @return View The rendered profile view (pages.account.profile)
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $data = compact('trooper');

        return view('pages.account.profile', $data);
    }
}
