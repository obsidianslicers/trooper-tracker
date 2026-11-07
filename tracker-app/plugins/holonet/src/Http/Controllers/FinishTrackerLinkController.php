<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Directory\TrackerDirectory;
use TroopTracker\Plugins\Holonet\Exceptions\TrackerUnavailableException;
use TroopTracker\Plugins\Holonet\Messages\Commands\RefreshTrackerLink;
use TroopTracker\Plugins\Holonet\Messages\Commands\SaveTrackerLink;
use TroopTracker\Plugins\Holonet\Support\TrackerClient;

/**
 * Where the other tracker sends the trooper back: swaps the code for an access
 * key, saves the link and copies the troop history.
 */
class FinishTrackerLinkController extends Controller
{
    public function __invoke(
        Request $request,
        TrackerDirectory $directory,
        TrackerClient $client,
    ): RedirectResponse {
        $pending = $request->session()->pull(StartTrackerLinkController::SESSION_KEY);
        $tracker = is_array($pending) ? $directory->find((string) $pending['tracker_url']) : null;

        if ($tracker === null || !hash_equals((string) $pending['state'], (string) $request->query('state')))
        {
            FlashType::danger('That link request has expired. Please try again.');

            return redirect()->route('plugins.holonet.index');
        }

        $code = $request->query('code');

        if (!is_string($code) || $code === '')
        {
            FlashType::warning("{$tracker->name} wasn't linked.");

            return redirect()->route('plugins.holonet.index');
        }

        try
        {
            $exchange = $client->exchangeCode($tracker->url, $code, (string) $pending['verifier']);
        }
        catch (TrackerUnavailableException $e)
        {
            FlashType::danger("{$tracker->name} couldn't be linked: {$e->getMessage()}");

            return redirect()->route('plugins.holonet.index');
        }

        $link = SaveTrackerLink::call(
            trooper: $request->user(),
            tracker: $tracker,
            token: $exchange['token'],
            remote_display_name: $exchange['display_name'],
        );

        RefreshTrackerLink::call(link: $link);

        FlashType::success("{$tracker->name} is linked.");

        return redirect()->route('plugins.holonet.index');
    }
}
