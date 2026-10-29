<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Account\PageData\SetupPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the trooper organization membership setup page.
 *
 * This controller retrieves the authenticated trooper's organization assignments
 * and renders the setup page where troopers can configure their organization
 * memberships and select which regions/units they belong to.
 */
class SetupController extends Controller
{
    /**
     * Handle the incoming request to display the organization setup page.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered organization setup page
     */
    public function __invoke(Request $request): View
    {
        $data = SetupPageData::call($request);

        return view('pages.account.setup', $data);
    }
}
