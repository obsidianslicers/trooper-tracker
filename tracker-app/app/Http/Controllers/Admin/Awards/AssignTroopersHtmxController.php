<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\Trooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Class AssignTroopersHtmxController
 *
 * Handles HTMX requests for the award assignment form.
 * @package App\Http\Controllers\Admin\Awards
 */
class AssignTroopersHtmxController extends Controller
{
    /**
     * Handle the HTMX request to add a selected trooper to the assignment list.
     *
     * @param Request $request The incoming HTTP request.
     * @param Award $award The award being assigned.
     * @return View The rendered trooper selection item.
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $trooperId = $request->get('trooper_id');
        $trooperName = $request->get('trooper_name');

        $trooper = Trooper::findOrFail($trooperId);

        // Verify trooper belongs to the award's organization
        $belongsToOrg = $trooper->organizations()->where('tt_organizations.id', $award->organization_id)->exists();
        if (!$belongsToOrg) {
            abort(403, 'Trooper does not belong to the award\'s organization');
        }

        $data = compact('trooper', 'award');

        return view('pages.admin.awards.assign-troopers-htmx', $data);
    }
}