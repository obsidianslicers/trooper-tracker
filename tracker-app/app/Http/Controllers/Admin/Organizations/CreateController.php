<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Enums\OrganizationType;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CreateController
 *
 * Handles displaying the form to create a new organization under a parent.
 */
class CreateController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Organizations', 'admin.organizations.list');
    }

    /**
     * Handle the request to display the organization creation page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to create a new sub-organization.
     *
     * @param  Request  $request  The incoming HTTP request object.
     * @param  Organization  $parent  The parent organization under which to create a new one.
     * @return View The rendered organization creation view.
     */
    public function __invoke(Request $request, Organization $parent): View
    {
        $this->authorize('update', $parent);

        $organization = new Organization;

        if ($parent->type == OrganizationType::ORGANIZATION)
        {
            $organization->type = OrganizationType::REGION;
        }
        elseif ($parent->type == OrganizationType::REGION)
        {
            $organization->type = OrganizationType::UNIT;
        }

        $data = [
            'parent' => $parent,
            'organization' => $organization,
        ];

        return view('pages.admin.organizations.create', $data);
    }
}
