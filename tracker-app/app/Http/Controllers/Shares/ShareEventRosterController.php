<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shares;

use App\Http\Controllers\MagicBusController;
use App\Models\Event;
use App\Models\EventShare;
use App\Models\EventUpload;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays a shared event roster.
 *
 * Renders a read-only roster view for an `Event` that was shared via an
 * `EventShare` token. This controller validates the share (revocation and
 * expiry), increments the share's view count, and returns the roster page.
 */
class ShareEventRosterController extends MagicBusController
{
    /**
     * Handle the incoming request and return the roster for a share token.
     *
     * Validates that the provided `EventShare` is viewable (not revoked and not
     * expired), increments the share's `view_count`, and returns the roster
     * view for the associated event.
     *
     * @param  Request    $request  The incoming HTTP request
     * @param  EventShare $share    The `EventShare` model instance
     * @return View The rendered roster sharing page view
     */
    public function __invoke(Request $request, EventShare $share): View
    {
        if ($share->is_viewable === false)
        {
            abort(403, 'This roster link has expired.');
        }

        $share->increment('view_count');

        $data = compact('share');

        return view('pages.shares.roster', $data);
    }
}
