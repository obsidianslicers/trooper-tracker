<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Messages\Commands\RefreshTrackerLink;
use TroopTracker\Plugins\Holonet\Models\HolonetLink;

/**
 * Copies a linked tracker's troop history again, now.
 */
class RefreshTrackerLinkController extends Controller
{
    public function __invoke(Request $request, HolonetLink $link): RedirectResponse
    {
        abort_unless($link->trooper_id === $request->user()->id, 404);

        $cooldown = (int) config('plugins.holonet.limits.refresh_cooldown_minutes');

        if ($link->refreshed_at !== null && $link->refreshed_at->gt(now()->subMinutes($cooldown)))
        {
            FlashType::info("Troops from {$link->tracker_name} are already up to date.");

            return redirect()->route('plugins.holonet.index');
        }

        if (RefreshTrackerLink::call(link: $link))
        {
            FlashType::success("Troops from {$link->tracker_name} are up to date.");
        }
        else
        {
            FlashType::warning("{$link->tracker_name} couldn't be refreshed: {$link->last_error}");
        }

        return redirect()->route('plugins.holonet.index');
    }
}
