<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\TrooperRequestStatus;
use App\Jobs\SendJoinRequestNotificationsJob;
use App\Models\TrooperRequest;

/**
 * Handler for submitting a trooper's club join request.
 *
 * @implements CommandHandlerInterface<SubmitTrooperRequestCommand>
 */
readonly class SubmitTrooperRequestCommandHandler implements CommandHandlerInterface
{
    /**
     * @param  SubmitTrooperRequestCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $organization = $message->organization;
        $trooper = $message->trooper;
        $primary_club = $organization->getPrimaryClub();

        // Cancel any other pending request in this primary-club family.
        TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::PENDING)
            ->where(TrooperRequest::PRIMARY_ORGANIZATION_ID, $primary_club->id)
            ->delete();

        $trooper_request = TrooperRequest::create([
            TrooperRequest::TROOPER_ID => $trooper->id,
            TrooperRequest::ORGANIZATION_ID => $organization->id,
            TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary_club->id,
            TrooperRequest::IDENTIFIER => $message->identifier ?: null,
            TrooperRequest::STATUS => TrooperRequestStatus::PENDING,
        ]);

        SendJoinRequestNotificationsJob::dispatch($trooper_request);

        return null;
    }
}
