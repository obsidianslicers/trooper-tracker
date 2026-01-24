<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CreateCostumeController
 *
 * Handles displaying the form to create a new costume under a parent organization.
 * @package App\Http\Controllers\Admin\Organizations
 */
class CreateCostumeController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Organizations', 'admin.organizations.list');
    }

    /**
     * Handle the request to display the costume creation page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to create a new costume for the organization.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Organization $organization The organization to create a costume for.
     * @return View The rendered costume creation view (pages.admin.organizations.create-costume).
     */
    public function __invoke(Request $request, Organization $organization): View
    {
        $this->authorize('update', $organization);

        $organization_costume = new OrganizationCostume();

        $data = compact('organization', 'organization_costume');

        return view('pages.admin.organizations.create-costume', $data);
    }
}
