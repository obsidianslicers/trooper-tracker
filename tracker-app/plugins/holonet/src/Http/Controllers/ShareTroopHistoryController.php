<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use TroopTracker\Plugins\Holonet\Messages\Queries\GetSharedTroopHistory;
use TroopTracker\Plugins\Holonet\Models\HolonetGrant;
use TroopTracker\Plugins\Holonet\Support\RequestLimit;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;

/**
 * Called by a linked tracker, server to server: the troop history of the
 * trooper whose access key it sends.
 */
class ShareTroopHistoryController extends Controller
{
    public const VERSION = 1;

    public function __invoke(Request $request, ThisTracker $this_tracker): JsonResponse
    {
        $token = (string) $request->bearerToken();
        $limited = RequestLimit::check([
            'history_per_ip' => (string) $request->ip(),
            'history_per_key' => hash('sha256', $token),
        ]);

        if ($limited !== null)
        {
            return $limited;
        }

        $grant = HolonetGrant::findByToken($token);
        $trooper = $grant?->trooper;

        if ($grant === null || $trooper === null)
        {
            return response()->json(['error' => 'invalid_token'], 401);
        }

        if (!$this_tracker->sharesHistory())
        {
            return response()->json(['error' => 'not_shared'], 403);
        }

        $grant->update([HolonetGrant::LAST_USED_AT => now()]);

        return response()->json([
            'version' => self::VERSION,
            'tracker' => ['name' => $this_tracker->name(), 'url' => $this_tracker->url()],
            'trooper' => ['display_name' => $trooper->display_name],
            'troops' => GetSharedTroopHistory::call(trooper: $trooper),
        ]);
    }
}
