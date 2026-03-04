<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares;

use App\Features\Events\Queries\GetSharedEventRosterQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Event;
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
class ShareEventRosterController extends MagicBusController
{
    /**
     * Handle the incoming request and return the roster for a share token.
     *
     * Increments the share's `view_count` and retrieves the event roster data
     * via `GetSharedEventRosterQuery`. Returns the roster view which will
     * conditionally display content based on the share's viewability (revoked
     * and expiry status are checked in the view layer).
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  EventShare  $share  The `EventShare` model instance bound by share_token
     * @return View The rendered roster sharing page view
     */
    public function __invoke(Request $request, EventShare $share): View
    {
        $share->increment('view_count');

        $query = new GetSharedEventRosterQuery($share);

        $event = $this->bus->send($query);

        $data = compact('share', 'event');

        return view('pages.shares.roster', $data);
    }
}
