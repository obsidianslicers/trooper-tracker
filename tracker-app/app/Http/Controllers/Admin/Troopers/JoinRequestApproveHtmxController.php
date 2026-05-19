<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\ApproveJoinRequestCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\TrooperOrganization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Approves a pending club join request via HTMX.
 *
 * Creates the TrooperAssignment, persists the identifier, and notifies the trooper.
 * Returns an updated join-request-card fragment for HTMX to swap in place.
 */
class JoinRequestApproveHtmxController extends MagicBusController
{
    public function __invoke(Request $request, TrooperOrganization $join_request): Response|View
    {
        $this->authorize('moderate', $join_request);

        $this->bus->send(new ApproveJoinRequestCommand($join_request));

        $join_request->load(['trooper', 'organization']);

        $data = compact('join_request');

        $message = json_encode([
            'message' => "{$join_request->trooper->display_name} approved for {$join_request->organization->name}!",
            'type' => 'success',
        ]);

        return response()
            ->view('pages.admin.troopers.join-request-card', $data)
            ->header('X-Flash-Message', $message);
    }
}
