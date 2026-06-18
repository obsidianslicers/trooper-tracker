<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Jobs\SendTrooperResubmittedNotificationsJob;
use App\Models\TrooperRequest;

/**
 * Handler for resubmitting a denied trooper's registration application.
 *
 * @implements CommandHandlerInterface<ResubmitDeniedTrooperCommand>
 */
readonly class ResubmitDeniedTrooperCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  ResubmitDeniedTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper = $message->trooper;

        $trooper->membership_status = MembershipStatus::PENDING;
        $trooper->save();

        TrooperRequest::where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->denied()
            ->update([
                TrooperRequest::STATUS        => TrooperRequestStatus::PENDING,
                TrooperRequest::DENIAL_REASON => null,
            ]);

        if ($message->organization !== null)
        {
            $primary_club = $message->organization->getPrimaryClub();

            TrooperRequest::create([
                TrooperRequest::TROOPER_ID              => $trooper->id,
                TrooperRequest::ORGANIZATION_ID         => $message->organization->id,
                TrooperRequest::PRIMARY_ORGANIZATION_ID => $primary_club->id,
                TrooperRequest::IDENTIFIER              => $message->identifier ?: null,
                TrooperRequest::STATUS                  => TrooperRequestStatus::PENDING,
            ]);
        }

        SendTrooperResubmittedNotificationsJob::dispatch($trooper);

        return null;
    }
}
