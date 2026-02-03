<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Changes\Queries\GetModelChangesForTrooperQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of a trooper's model change audit history page.
 *
 * Presents a trooper's recent audit trail showing changes to their profile
 * and event participation records for administrators and moderators to review.
 */
class ChangesController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's change history page
     *
     * This method authorizes the user, sets up breadcrumbs, loads the trooper's
     * recent model changes via GetModelChangesForTrooperQuery, and returns the view
     * showing the trooper's audit trail of changes.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper whose change history is to be displayed
     * @return View The rendered changes history page view
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $lookback = now()->subDays(30);

        $changes_query = new GetModelChangesForTrooperQuery($trooper, $lookback);

        $changes = $this->bus->send($changes_query);

        $data = compact('trooper', 'changes');

        return view('pages.admin.troopers.changes', $data);
    }
}
