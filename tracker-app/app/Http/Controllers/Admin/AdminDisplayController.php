<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\MembershipStatus;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of the main administration dashboard.
 *
 * This controller provides a summary of administrative tasks, such as displaying
 * the count of troopers pending approval and setting a relevant flash message.
 */
class AdminDisplayController extends MagicBusController
{
    /**
     * Handle the incoming request to display the admin dashboard
     *
     * It calculates the number of troopers pending approval, sets a corresponding
     * flash message, and renders the main admin view.
     *
     * @param  Request  $request  The incoming HTTP request
     * @return View The rendered admin dashboard view or a redirect response
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $not_approved = Trooper::pendingApprovals()->moderatedBy($trooper)->count();

        $pending_join_requests = TrooperOrganization::pending()
            ->whereHas('trooper', fn ($q) => $q->where(Trooper::MEMBERSHIP_STATUS, '!=', MembershipStatus::PENDING))
            ->forModerator($trooper)
            ->count();

        $this->buildApprovalFlashMessages($not_approved, $pending_join_requests);

        $data = compact('not_approved', 'pending_join_requests');

        return view('pages.admin.display', $data);
    }

    private function buildApprovalFlashMessages(int $not_approved, int $pending_join_requests): void
    {
        if ($not_approved === 1)
        {
            $this->flash->warning("There is {$not_approved} trooper ready for action!");
        }
        elseif ($not_approved > 1)
        {
            $this->flash->warning("There are {$not_approved} troopers ready for action!");
        }

        if ($pending_join_requests === 1)
        {
            $this->flash->warning('1 trooper has a pending request!');
        }
        elseif ($pending_join_requests > 1)
        {
            $this->flash->warning("{$pending_join_requests} troopers have a pending request!");
        }
    }
}
