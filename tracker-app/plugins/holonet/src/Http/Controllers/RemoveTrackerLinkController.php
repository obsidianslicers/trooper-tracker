<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Messages\Commands\RemoveTrackerLink;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;

/**
 * Unlinks a tracker and removes the troops copied from it.
 */
class RemoveTrackerLinkController extends Controller
{
    public function __invoke(Request $request, HolonetLink $link): RedirectResponse
    {
        abort_unless($link->trooper_id === $request->user()->id, 404);

        RemoveTrackerLink::call(link: $link);

        FlashType::success("{$link->tracker_name} is no longer linked.");

        return redirect()->route('plugins.holonet.index');
    }
}
