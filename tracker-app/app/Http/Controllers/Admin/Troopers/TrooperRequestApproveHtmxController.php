<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Features\Troopers\Commands\ApproveTrooperRequestCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\TrooperRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Approves a pending club join request via HTMX.
 *
 * Creates TrooperOrganization at the primary club, creates TrooperAssignment at the
 * requested organization, and notifies the trooper.
 * Returns an updated trooper-request-card fragment for HTMX to swap in place.
 */
class TrooperRequestApproveHtmxController extends MagicBusController
{
    public function __invoke(Request $request, TrooperRequest $trooper_request): Response|View
    {
        $this->authorize('moderate', $trooper_request);

        $this->bus->send(new ApproveTrooperRequestCommand($trooper_request));

        $trooper_request->load(['trooper', 'organization', 'primaryOrganization']);

        $data = compact('trooper_request');

        $message = json_encode([
            'message' => "{$trooper_request->trooper->display_name} approved for {$trooper_request->organization->name}!",
            'type' => 'success',
        ]);

        return response()
            ->view('pages.admin.troopers.trooper-request-card', $data)
            ->header('X-Flash-Message', $message);
    }
}
