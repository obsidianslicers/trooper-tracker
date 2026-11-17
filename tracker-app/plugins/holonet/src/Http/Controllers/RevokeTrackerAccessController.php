<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Messages\Commands\RevokeTrackerGrant;
use TroopTracker\Plugins\Holonet\Models\HolonetGrant;

/**
 * Stops another tracker reading the trooper's history here.
 */
class RevokeTrackerAccessController extends Controller
{
    public function __invoke(Request $request, HolonetGrant $grant): RedirectResponse
    {
        abort_unless($grant->trooper_id === $request->user()->id, 404);

        RevokeTrackerGrant::call(grant: $grant);

        FlashType::success(__('holonet::account.:tracker can no longer see your troop history here.', [
            'tracker' => $grant->tracker_name,
        ]));

        return redirect()->route('plugins.holonet.index');
    }
}
