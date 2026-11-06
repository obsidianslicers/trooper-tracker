<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Federation\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TroopTracker\Plugins\Federation\Messages\Commands\RevokeTrackerGrant;
use TroopTracker\Plugins\Federation\Models\TrackerGrant;

/**
 * Called by a linked tracker when the trooper unlinks there: forgets the access key.
 */
class ForgetAccessKeyController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $grant = TrackerGrant::findByToken((string) $request->bearerToken());

        if ($grant !== null)
        {
            RevokeTrackerGrant::call(grant: $grant);
        }

        return response()->noContent();
    }
}
