<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\DenyTrooperRequestCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\TrooperRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Denies a pending club join request via HTMX.
 *
 * Marks the join request denied, persists the reason, and notifies the trooper.
 * Returns an updated trooper-request-card fragment for HTMX to swap in place.
 */
class TrooperRequestDenyHtmxController extends MagicBusController
{
    public function __invoke(Request $request, TrooperRequest $trooper_request): Response|View
    {
        $this->authorize('moderate', $trooper_request);

        $validated = $request->validate(['denial_reason' => 'nullable|string|max:1000']);

        $this->bus->send(new DenyTrooperRequestCommand($trooper_request, $validated['denial_reason'] ?? null));

        $trooper_request->load(['trooper', 'organization', 'primaryOrganization']);

        $data = compact('trooper_request');

        $message = json_encode([
            'message' => "{$trooper_request->trooper->display_name}'s request for {$trooper_request->organization->name} denied.",
            'type' => 'danger',
        ]);

        return response()
            ->view('pages.admin.troopers.trooper-request-card', $data)
            ->header('X-Flash-Message', $message);
    }
}
