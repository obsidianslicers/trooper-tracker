<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Federation\Messages\Commands\RemoveTrackerLink;
use TroopTracker\Plugins\Federation\Models\TrackerLink;

/**
 * Unlinks a tracker and removes the troops copied from it.
 */
class RemoveTrackerLinkController extends Controller
{
    public function __invoke(Request $request, TrackerLink $link): RedirectResponse
    {
        abort_unless($link->trooper_id === $request->user()->id, 404);

        RemoveTrackerLink::call(link: $link);

        FlashType::success("{$link->tracker_name} is no longer linked.");

        return redirect()->route('plugins.federation.index');
    }
}
