<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Queries\GetTrooperMembershipsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of a trooper's membership management page.
 *
 * Presents organizational memberships and related assignments for a given trooper
 * so administrators and moderators can review or adjust membership details.
 */
class MembershipController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's membership page.
     *
     * This method authorizes the user, sets up breadcrumbs, loads the trooper's
     * organization memberships via GetTrooperMembershipsQuery, and returns the view
     * for managing a specific trooper's organization memberships.
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The trooper whose organization memberships are to be displayed.
     * @return View The rendered authority page view.
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $membership_query = new GetTrooperMembershipsQuery($trooper);

        $organization_memberships = $this->bus->send($membership_query);

        $data = compact('trooper', 'organization_memberships');

        return view('pages.admin.troopers.membership', $data);
    }
}
