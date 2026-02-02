<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Class ListController
 *
 * Handles the display of the main organizations list in the admin section.
 * This controller fetches and displays a list of organizations with their assignment
 * status for the authenticated user.
 */
class ListController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the request to display the organizations list page.
     *
     * Sets up breadcrumbs, retrieves organizations with assignment data for the
     * authenticated user, and returns the list view.
     *
     * @param  Request  $request  The incoming HTTP request object.
     * @return View The rendered organizations list view.
     */
    public function __invoke(Request $request): View
    {
        $organizations = Organization::withAllAssignments(Auth::user()->id)->get();

        $data = [
            'organizations' => $organizations,
        ];

        return view('pages.admin.organizations.list', $data);
    }
}
