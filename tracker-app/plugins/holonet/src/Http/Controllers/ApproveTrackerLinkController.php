<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Messages\Commands\CreateLinkCode;
use TroopTracker\Plugins\Holonet\Support\LinkEligibility;
use TroopTracker\Plugins\Holonet\Support\LinkRequest;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;

/**
 * The trooper's answer: sends them back to the other tracker with a one-time
 * code if they allowed the link, or without one if they didn't.
 */
class ApproveTrackerLinkController extends Controller
{
    public function __invoke(
        Request $request,
        TrackerDirectory $directory,
        ThisTracker $this_tracker,
    ): RedirectResponse {
        $link_request = LinkRequest::from($request, $directory);

        abort_if($link_request === null, 400, 'This link request isn\'t valid.');

        if (!$request->boolean('allow')
            || !$this_tracker->sharesHistory()
            || !LinkEligibility::canLink($request->user()))
        {
            return redirect()->away($link_request->returnUrl(['error' => 'access_denied']));
        }

        $code = CreateLinkCode::call(
            trooper: $request->user(),
            tracker: $link_request->tracker,
            code_challenge: $link_request->code_challenge,
        );

        return redirect()->away($link_request->returnUrl(['code' => $code]));
    }
}
