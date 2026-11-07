<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use TroopTracker\Plugins\Holonet\Directory\DirectoryTracker;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Exceptions\TrackerUnavailableException;
use TroopTracker\Plugins\Holonet\Support\LinkEligibility;
use TroopTracker\Plugins\Holonet\Support\Pkce;
use TroopTracker\Plugins\Holonet\Support\RequestLimit;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;
use TroopTracker\Plugins\Holonet\Support\TrackerClient;

/**
 * Sends the trooper to the tracker they picked, to sign in there and allow the link.
 */
class StartTrackerLinkController extends Controller
{
    public const SESSION_KEY = 'holonet.link';

    public function __invoke(
        Request $request,
        TrackerDirectory $directory,
        ThisTracker $this_tracker,
        TrackerClient $client,
    ): RedirectResponse {
        $validated = $request->validate(['tracker_url' => ['required', 'string', 'max:255']]);
        $tracker = $directory->others()->get(TrackerDirectory::normalizeUrl($validated['tracker_url']));

        if ($tracker === null || !LinkEligibility::canLink($request->user()))
        {
            FlashType::danger('That tracker can\'t be linked.');

            return redirect()->route('plugins.holonet.index');
        }

        if (!RequestLimit::attemptLink($request->user()->id))
        {
            FlashType::danger('Too many link attempts. Please try again in an hour.');

            return redirect()->route('plugins.holonet.index');
        }

        $problem = $this->problemWith($tracker, $client);

        if ($problem !== null)
        {
            FlashType::warning("{$tracker->name} can't be linked: {$problem}");

            return redirect()->route('plugins.holonet.index');
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

    /**
     * Why the trooper shouldn't be sent over, or null. Saves them landing on a
     * page that doesn't exist when the other tracker hasn't turned HoloNet on.
     */
    private function problemWith(DirectoryTracker $tracker, TrackerClient $client): ?string
    {
        try
        {
            return $client->sharesHistory($tracker->url)
                ? null
                : 'it doesn\'t share troop history with other trackers.';
        }
        catch (TrackerUnavailableException $e)
        {
            return $e->getMessage();
        }
    }
}
