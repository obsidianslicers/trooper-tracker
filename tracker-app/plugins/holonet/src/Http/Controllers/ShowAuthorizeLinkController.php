<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Support\LinkEligibility;
use TroopTracker\Plugins\Holonet\Support\LinkRequest;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;

/**
 * Asks the signed-in trooper whether another tracker may read their troop history here.
 */
class ShowAuthorizeLinkController extends Controller
{
    public function __invoke(
        Request $request,
        TrackerDirectory $directory,
        ThisTracker $this_tracker,
    ): View {
        $link_request = LinkRequest::from($request, $directory);

        return view('holonet::authorize', [
            'link_request' => $link_request,
            'problem' => match (true)
            {
                $link_request === null => 'This link request isn\'t valid, or comes from a tracker '
                    .'that isn\'t on the list of supported trackers.',
                !$this_tracker->sharesHistory() => "{$this_tracker->name()} doesn't share troop "
                    .'history with other trackers.',
                !LinkEligibility::canLink($request->user()) => 'Troopers under 18 can\'t link trackers.',
                default => null,
            },
            'tracker_name' => $this_tracker->name(),
        ]);
    }
}
