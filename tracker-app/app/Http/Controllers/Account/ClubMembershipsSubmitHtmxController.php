<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Commands\SubmitJoinRequestCommand;
use App\Features\Troopers\Commands\UpdateTrooperIdentifiersCommand;
use App\Features\Troopers\Commands\UpdateTrooperMembershipsCommand;
use App\Features\Troopers\Commands\UpdateTrooperNotificationsCommand;
use App\Http\Controllers\MagicBusController;
use App\Http\Requests\Account\ClubMembershipRequest;
use App\Models\Organization;
use App\Enums\MembershipStatus;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

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
        $primary_organization = $organization->getPrimaryClub();

        //  primary club identifier and membership status update
        $organization_data = [
            $primary_organization->id => [
                'identifier' => $request->validated('identifier'),
                'assignment' => $primary_organization->id,
                'membership_status' => MembershipStatus::PENDING->value,
            ],
        ];

        $identifier_command = new UpdateTrooperIdentifiersCommand($trooper, $organization_data);
        $this->bus->send($identifier_command);

        // membership update for the organization picked
        $membership = [
            $primary_organization->id =>
                [
                    'assignment' => $trooper->is_visitor ? $primary_organization->id : $organization->id,
                ],
        ];
        $membership_command = new UpdateTrooperMembershipsCommand($trooper, $membership);
        $this->bus->send($membership_command);


        $notification = [
            $primary_organization->id => [
                'should_notify' => true,
            ],
        ];
        $notification_command = new UpdateTrooperNotificationsCommand($trooper, $notification);
        $this->bus->send($notification_command);

        // $command = new SubmitJoinRequestCommand(
        //     $trooper,
        //     $organization,
        //     $request->filled('identifier') ? $request->string('identifier')->toString() : null,
        // );

        // $this->bus->send($command);

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
