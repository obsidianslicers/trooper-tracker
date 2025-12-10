<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Trooper;
use App\Services\BreadCrumbService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class AuthorityController
 *
 * Handles the display of a trooper's authority management page.
 * @package App\Http\Controllers\Admin\Troopers
 */
class AuthorityController extends Controller
{
    /**
     * AuthorityController constructor.
     *
     * @param BreadCrumbService $crumbs The breadcrumb service for managing navigation history.
     */
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's authority page.
     *
     * This method authorizes the user, sets up breadcrumbs, and returns the view
     * for managing a specific trooper's roles and organizational assignments.
     *
     * @param Request $request The incoming HTTP request.
     * @param Trooper $trooper The trooper whose authorities are to be displayed.
     * @return View The rendered authority page view.
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('updateAuthority', $trooper);

        $organization_authorities = Organization::withAllAssignments($trooper->id)->get();

        $data = [
            'trooper' => $trooper,
            'organization_authorities' => $organization_authorities,
        ];

        return view('pages.admin.troopers.authority', $data);
    }
}
