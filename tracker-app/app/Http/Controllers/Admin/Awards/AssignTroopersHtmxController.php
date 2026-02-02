<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class AssignTroopersHtmxController
 *
 * Handles HTMX requests for the award assignment form.
 */
class AssignTroopersHtmxController extends MagicBusController
{
    /**
     * Handle the HTMX request to add a selected trooper to the assignment list.
     *
     * @param  Request  $request  The incoming HTTP request.
     * @param  Award  $award  The award being assigned.
     * @return View The rendered trooper selection item.
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $trooper_id = $request->get('trooper_id');
        $trooper_name = $request->get('trooper_name');

        $trooper = Trooper::findOrFail($trooper_id);

        // Verify trooper belongs to the award's organization
        $trooper_belongs = $award->visibleTo($trooper)->exists();

        if (!$trooper_belongs)
        {
            abort(403, 'Trooper does not belong to the awards organization');
        }

        $data = compact('trooper', 'award');

        return view('pages.admin.awards.assign-troopers-htmx', $data);
    }
}
