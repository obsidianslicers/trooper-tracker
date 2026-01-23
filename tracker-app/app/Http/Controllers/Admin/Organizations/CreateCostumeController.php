<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CreateCostumeController
 *
 * Handles displaying the form to create a new costume under a parent organization.
 * @package App\Http\Controllers\Admin\Organizations
 */
class CreateCostumeController extends Controller
{
    /**
     * CreateCostumeController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
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
