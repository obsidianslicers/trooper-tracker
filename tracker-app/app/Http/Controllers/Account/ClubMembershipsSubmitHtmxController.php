<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\SubmitJoinRequestCommand;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Rules\Auth\UniqueOrganizationIdentifierRule;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Submits a club join request for the authenticated trooper via HTMX.
 *
 * Returns an updated row partial showing the pending state.
 */
class ClubMembershipsSubmitHtmxController extends MagicBusController
{
    /**
     * @param  Request  $request
     * @return Response|View
     */
    public function __invoke(Request $request): Response|View
    {
        $trooper = $request->user();

        $request->validate([
            'organization_id' => ['required', 'integer', Rule::exists('tt_organizations', 'id')],
        ]);

        /** @var Organization $organization */
        $organization = Organization::findOrFail($request->integer('organization_id'));

        // Ensure the organization is a leaf node (unit) to prevent tampering
        if ($organization->organizations()->count() > 0)
        {
            throw ValidationException::withMessages([
                'organization_id' => 'Please select a unit-level organization.',
            ]);
        }

        // Validate identifier against organization-specific rules if applicable
        $identifier_rules = ['nullable', 'string', 'max:64'];

        if (!empty($organization->identifier_validation))
        {
            $base_rules = explode('|', $organization->identifier_validation);
            $identifier_rules = array_merge(
                ['nullable'],
                $base_rules,
                [new UniqueOrganizationIdentifierRule($organization, $trooper)]
            );
        }

        $request->validate([
            'identifier' => $identifier_rules,
        ]);

        $command = new SubmitJoinRequestCommand(
            $trooper,
            $organization,
            $request->filled('identifier') ? $request->string('identifier')->toString() : null,
        );

        $this->bus->send($command);

        $data = compact('organization');

        $message = json_encode([
            'message' => "Your request to join {$organization->name} has been submitted.",
            'type'    => 'success',
        ]);

        return response()
            ->view('pages.account.club-memberships-row', $data)
            ->header('X-Flash-Message', $message);
    }
}
