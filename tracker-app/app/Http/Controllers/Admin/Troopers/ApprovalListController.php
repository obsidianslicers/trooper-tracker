<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Queries\GetTrooperApprovalsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class TrooperApprovalDisplayController
 *
 * Handles the display of troopers pending approval.
 */
class ApprovalListController extends MagicBusController
{
    protected function initialized()
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the request to display the trooper approvals page.
     *
     * This method retrieves all troopers with a 'pending' status. For non-admin users,
     * it filters the list to show only troopers they are responsible for moderating.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @return View A view containing the list of troopers pending approval.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $approval_query = new GetTrooperApprovalsQuery($trooper);

        $troopers = $this->bus->send($approval_query);

        $data = compact('troopers');

        return view('pages.admin.troopers.approvals', $data);
    }
}
