<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Bus\MagicBus;
use App\Enums\MembershipStatus;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\MembershipApprovedNotification;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Illuminate\Support\Facades\DB;

/**
 * Handler for approving a trooper's membership application.
 *
 * @implements CommandHandlerInterface<ApproveTrooperCommand>
 */
readonly class ApproveTrooperCommandHandler implements CommandHandlerInterface
{
    public function __construct(private MagicBus $bus) {}

    /**
     * @param  ApproveTrooperCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        if ($message->is_approved)
        {
            DB::transaction(function () use ($message): void {
                $message->trooper->membership_status = MembershipStatus::ACTIVE;

                if ($message->trooper->is_visitor)
                {
                    $message->trooper->visitor_expires_at = now()->addMonths(6);
                    $message->trooper->visitor_notified_at = null;
                }

                $message->trooper->save();

                TrooperRequest::where(TrooperRequest::TROOPER_ID, $message->trooper->id)
                    ->pending()
                    ->get()
                    ->each(function (TrooperRequest $trooper_request): void {
                        $this->bus->send(new ApproveTrooperRequestCommand($trooper_request, suppress_notification: true));
                    });
            });

            $message->trooper->notify(new MembershipApprovedNotification);

            return null;
        }

        $message->trooper->membership_status = MembershipStatus::DENIED;
        $message->trooper->save();

        if (!$message->is_approved)
        {
            TrooperRequest::where(TrooperRequest::TROOPER_ID, $message->trooper->id)
                ->pending()
                ->get()
                ->each(function (TrooperRequest $trooper_request) use ($message): void {
                    $this->bus->send(new DenyTrooperRequestCommand(
                        $trooper_request,
                        $message->denial_reason,
                        suppress_notification: true
                    ));
                });

            $message->trooper->notify(new TrooperDeniedNotification($message->denial_reason));
        }

        return null;
    }
}
