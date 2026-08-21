<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Messages\Account\PageData\AccountPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Displays the authenticated trooper's profile management page.
 *
 * This controller renders the profile page where troopers can view and
 * manage their personal information, contact details, and account settings.
 */
class IndexController extends Controller
{
    /**
     * Handle the incoming request to display the profile page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|SymfonyResponse The rendered profile page view
     */
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = AccountPageData::call($request);

        return Inertia::render('account/Index', $data);
    }
}
