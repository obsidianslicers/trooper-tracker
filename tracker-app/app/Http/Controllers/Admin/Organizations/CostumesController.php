<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CostumesController
 *
 * Handles displaying the form to manage costumes for an organization.
 */
class CostumesController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Organizations', 'admin.organizations.list');
    }

    /**
     * Handle the request to display the organization costumes page
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to manage costumes for an organization.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @param  Organization  $organization  The organization whose costumes are to be managed
     * @return View The rendered organization costumes view
     */
    public function __invoke(Request $request, Organization $organization): View
    {
        $this->authorize('update', $organization);

        $organization->load([
            'organization_costumes' => function ($query) {
                $query->orderBy(OrganizationCostume::NAME);
            },
        ]);

        $data = compact('organization');

        return view('pages.admin.organizations.costumes', $data);
    }
}
