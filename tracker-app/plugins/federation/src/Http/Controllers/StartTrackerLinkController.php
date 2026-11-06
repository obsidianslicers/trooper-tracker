<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TroopTracker\Plugins\Federation\Directory\TrackerDirectory;
use TroopTracker\Plugins\Federation\Support\LinkEligibility;
use TroopTracker\Plugins\Federation\Support\Pkce;
use TroopTracker\Plugins\Federation\Support\ThisTracker;

/**
 * Sends the trooper to the tracker they picked, to sign in there and allow the link.
 */
class StartTrackerLinkController extends Controller
{
    public const SESSION_KEY = 'federation.link';

    public function __invoke(
        Request $request,
        TrackerDirectory $directory,
        ThisTracker $this_tracker,
    ): RedirectResponse {
        $validated = $request->validate(['tracker_url' => ['required', 'string', 'max:255']]);
        $tracker = $directory->others()->get(TrackerDirectory::normalizeUrl($validated['tracker_url']));

        if ($tracker === null || !LinkEligibility::canLink($request->user()))
        {
            FlashType::danger('That tracker can\'t be linked.');

            return redirect()->route('plugins.federation.index');
        }

        $state = Str::random(40);
        $verifier = Str::random(64);

        $request->session()->put(self::SESSION_KEY, [
            'state' => $state,
            'verifier' => $verifier,
            'tracker_url' => $tracker->url,
        ]);

        return redirect()->away($tracker->url.ThisTracker::AUTHORIZE_PATH.'?'.http_build_query([
            'client' => $this_tracker->url(),
            'redirect_uri' => ThisTracker::callbackUrlFor($this_tracker->url()),
            'state' => $state,
            'code_challenge' => Pkce::challenge($verifier),
        ]));
    }
}
