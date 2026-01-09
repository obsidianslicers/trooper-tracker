<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Organizations;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class CostumesController
 *
 * Handles displaying the form to manage costumes for an organization.
 * @package App\Http\Controllers\Admin\Organizations
 */
class CostumesController extends Controller
{
    /**
     * CostumesController constructor.
     *
     * @param BreadCrumbService $crumbs The service for managing breadcrumbs.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Organizations', 'admin.organizations.list');
    }

    /**
     * Handle the request to display the organization costumes page.
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view
     * containing the form to manage costumes for an organization.
     *
     * @param Request $request The incoming HTTP request object.
     * @param Organization $organization The organization whose costumes are to be managed.
     * @return View The rendered organization costumes view.
     */
    public function __invoke(Request $request, Organization $organization): View
    {
        $this->authorize('update', $organization);

        $data = [
            'organization' => $organization
        ];

        return view('pages.admin.organizations.costumes', $data);
    }
}
