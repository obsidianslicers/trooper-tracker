<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TroopTracker\Plugins\Holonet\Messages\Commands\RevokeTrackerGrant;
use TroopTracker\Plugins\Holonet\Models\HolonetGrant;
use TroopTracker\Plugins\Holonet\Support\RequestLimit;

/**
 * Called by a linked tracker when the trooper unlinks there: forgets the access key.
 */
class ForgetAccessKeyController extends Controller
{
    public function __invoke(Request $request): Response|JsonResponse
    {
        $limited = RequestLimit::check(['revoke_per_ip' => (string) $request->ip()]);

        if ($limited !== null)
        {
            return $limited;
        }

        $grant = HolonetGrant::findByToken((string) $request->bearerToken());

        if ($grant !== null)
        {
            RevokeTrackerGrant::call(grant: $grant);
        }

        return response()->noContent();
    }
}
