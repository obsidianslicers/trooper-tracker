<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use App\Models\AwardTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the form to assign an award to a single trooper.
 *
 * Displays the single-trooper award assignment form, allowing administrators
 * and moderators to award a specific trooper with an award on a specified date.
 */
class AssignTrooperController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award assignment page
     *
     * Authorizes the user, sets up breadcrumbs, and returns the view containing
     * the form to assign the award to troopers.
     *
     * @param  Request  $request  The incoming HTTP request object
     * @param  Award  $award  The award to assign
     * @return View The rendered award assignment view
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $award_trooper = new AwardTrooper;

        $award_date = $award->frequency->normalizeDate(now());

        $data = compact('award', 'award_trooper', 'award_date');

        return view('pages.admin.awards.assign-trooper', $data);
    }
}
