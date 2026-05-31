<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\DenyJoinRequestCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\TrooperOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Denies a pending club join request via HTMX.
 *
 * Sets the membership status to DENIED and notifies the trooper.
 * Returns an updated join-request-card fragment for HTMX to swap in place.
 */
class JoinRequestDenyHtmxController extends MagicBusController
{
    public function __invoke(Request $request, TrooperOrganization $join_request): Response|View
    {
        $this->authorize('moderate', $join_request);

        $validated = $request->validate(['denial_reason' => 'nullable|string|max:1000']);

        $this->bus->send(new DenyJoinRequestCommand($join_request, $validated['denial_reason'] ?? null));

        $join_request->load(['trooper', 'organization']);

        $data = compact('join_request');

        $message = json_encode([
            'message' => "{$join_request->trooper->display_name}'s request for {$join_request->organization->name} denied.",
            'type' => 'danger',
        ]);

        return response()
            ->view('pages.admin.troopers.join-request-card', $data)
            ->header('X-Flash-Message', $message);
    }
}
