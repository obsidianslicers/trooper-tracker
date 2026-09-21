<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\Membership\MembershipApprovalsPageData;
use App\Services\BreadCrumbService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * Class MembershipApprovalsController
 *
 * Handles the display of troopers pending approval.
 */
class MembershipApprovalsController extends Controller
{
    public function __construct(private readonly BreadCrumbService $crumbs)
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
    }

    /**
     * Handle the request to display the trooper approvals page
     *
     * This method retrieves all troopers with a 'pending' status. For non-admin users,
     * it filters the list to show only troopers they are responsible for moderating.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return InertiaResponse|SymfonyResponse A view containing the list of troopers pending approval
     */
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse
    {
        $data = MembershipApprovalsPageData::call($request);

        return Inertia::render('admin/troopers/MembershipApprovals', $data);
    }
}
