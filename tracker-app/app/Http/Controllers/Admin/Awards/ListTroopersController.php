<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Awards;

use App\Http\Controllers\MagicBusController;
use App\Models\Award;
use App\Models\AwardTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Handles displaying the list of troopers assigned to an award.
 */
class ListTroopersController extends MagicBusController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute('Command Staff', 'admin.display');
        $this->crumbs->addRoute('Awards', 'admin.awards.list');
    }

    /**
     * Handle the request to display the award trooper list page.
     *
     * Authorizes the trooper and returns the view containing the award's
     * assigned troopers.
     *
     * @param  Request  $request  The incoming HTTP request object.
     * @param  Award  $award  The award being viewed.
     * @return View The rendered award trooper list view.
     */
    public function __invoke(Request $request, Award $award): View
    {
        $this->authorize('update', $award);

        $troopers = $award->troopers()->orderByDesc(AwardTrooper::AWARD_DATE)->get();

        $data = compact('award', 'troopers');

        return view('pages.admin.awards.list-troopers', $data);
    }
}
