<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\SubmitTrooperRequestCommand;
use App\Features\Troopers\Exceptions\DuplicateOrganizationIdentifierException;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ClubMembershipRequest;
use App\Models\Organization;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

/**
 * Submits a club join request for the authenticated trooper via HTMX.
 *
 * Returns an updated row partial showing the pending state.
 */
class ClubMembershipsSubmitHtmxController extends MagicBusController
{
    public function __invoke(ClubMembershipRequest $request): Response|View
    {
        $trooper = $request->user();

        $organization = Organization::findOrFail($request->integer('organization_id'));

        $command = new SubmitTrooperRequestCommand(
            $trooper,
            $organization,
            $request->filled('identifier') ? $request->string('identifier')->toString() : null,
        );

        try
        {
            $this->bus->send($command);
        }
        catch (DuplicateOrganizationIdentifierException $exception)
        {
            throw ValidationException::withMessages([
                'identifier' => $exception->flashMessage(),
            ]);
        }

        $org = $organization;
        $org->is_pending = true;
        $data = compact('org');

        $message = json_encode([
            'message' => "Your request to join {$organization->name} has been submitted.",
            'type' => 'success',
        ]);

        return response()
            ->view('pages.account.club-memberships-row', $data)
            ->header('X-Flash-Message', $message);
    }
}
