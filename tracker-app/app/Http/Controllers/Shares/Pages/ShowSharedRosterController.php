<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\Commands\RecordEventShareView;
use App\Messages\Events\PageData\SharedRosterPageData;
use App\Models\EventShare;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays a shared event roster via EventShare token.
 *
 * Renders a read-only roster view for an `Event` that was shared via an
 * `EventShare` token. Increments the share's view count and retrieves the
 * roster data. The view layer conditionally displays content based on the
 * share's viewability status (revocation and expiry).
 */
class ShowSharedRosterController extends PageController
{
    public function __invoke(Request $request, EventShare $share): View
    {
        RecordEventShareView::call(event_share: $share);

        $data = SharedRosterPageData::call($request);

        return view('pages.shares.roster', $data);
    }
}
