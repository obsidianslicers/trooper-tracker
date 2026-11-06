<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use TroopTracker\Plugins\Holonet\Messages\Commands\ExchangeLinkCode;
use TroopTracker\Plugins\Holonet\Support\ThisTracker;

/**
 * Called by another tracker, server to server: swaps a one-time code for an
 * access key to the trooper's troop history.
 */
class ExchangeLinkCodeController extends Controller
{
    public function __invoke(Request $request, ThisTracker $this_tracker): JsonResponse
    {
        if (RateLimiter::tooManyAttempts('holonet-token:'.$request->ip(), 30))
        {
            return response()->json(['error' => 'too_many_requests'], 429);
        }

        RateLimiter::hit('holonet-token:'.$request->ip());

        if (!$this_tracker->sharesHistory())
        {
            return response()->json(['error' => 'not_shared'], 403);
        }

        $code = $request->input('code');
        $verifier = $request->input('code_verifier');
        $client = $request->input('client');

        $result = is_string($code) && is_string($verifier) && is_string($client)
            ? ExchangeLinkCode::call(code: $code, code_verifier: $verifier, client: $client)
            : null;

        if ($result === null)
        {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        return response()->json([
            'token' => $result['token'],
            'tracker' => ['name' => $this_tracker->name(), 'url' => $this_tracker->url()],
            'trooper' => ['display_name' => $result['trooper']->display_name],
        ]);
    }
}
