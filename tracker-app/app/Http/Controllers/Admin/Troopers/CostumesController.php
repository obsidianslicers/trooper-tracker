<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles the display of a trooper's costumes page.
 *
 * Presents a trooper's recent costume participation history for administrators
 * and moderators to review their activity and attendance records.
 */
class CostumesController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Troopers', 'admin.troopers.list');
    }

    /**
     * Handle the incoming request to display a trooper's costumes page
     *
     * This method authorizes the user, sets up breadcrumbs, loads the trooper's
     * recent costumes via GetMostRecentCostumesForTrooperQuery, and returns the view
     * showing the trooper's costume participation history.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Trooper  $trooper  The trooper whose costumes history is to be displayed
     * @return View The rendered costumes page view
     */
    public function __invoke(Request $request, Trooper $trooper): View
    {
        $this->authorize('update', $trooper);

        $trooper_costumes = $trooper->trooper_costumes()->with('organization_costume.costume')->get();

        $data = compact('trooper', 'trooper_costumes');

        return view('pages.admin.troopers.costumes', $data);
    }
}
