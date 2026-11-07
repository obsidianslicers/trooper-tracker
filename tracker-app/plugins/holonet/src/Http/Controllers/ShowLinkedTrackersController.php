<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\PageController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Messages\PageData\LinkedTrackersPageData;

/**
 * The trooper's linked trackers, and the trackers that can read their history.
 */
class ShowLinkedTrackersController extends PageController
{
    public function __invoke(Request $request): View
    {
        $this->crumbs->addRoute('Profile', 'account.index');
        $this->crumbs->add('HoloNet');

        return view(
            'holonet::linked-trackers',
            LinkedTrackersPageData::call(trooper: $request->user()),
        );
    }
}
