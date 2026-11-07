<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Support\RequestLimit;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;

/**
 * Tells another tracker that HoloNet is running here, before it sends a trooper over.
 */
class ShowTrackerCardController extends Controller
{
    public function __invoke(Request $request, ThisTracker $this_tracker): JsonResponse
    {
        $limited = RequestLimit::check(['instance_per_ip' => (string) $request->ip()]);

        if ($limited !== null)
        {
            return $limited;
        }

        return response()->json([
            'holonet' => ShareTroopHistoryController::VERSION,
            'name' => $this_tracker->name(),
            'url' => $this_tracker->url(),
            'shares_history' => $this_tracker->sharesHistory(),
        ]);
    }
}
