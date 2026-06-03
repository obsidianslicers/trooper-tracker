<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Mail\Account\AccountDeletionCancelledMail;
use Illuminate\Support\Facades\Mail;

/**
 * @implements CommandHandlerInterface<CancelAccountDeletionCommand>
 */
readonly class CancelAccountDeletionCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  CancelAccountDeletionCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper = $message->trooper;

        $trooper->forceFill(['deletion_requested_at' => null])->save();

        Mail::to($trooper->email)->queue(new AccountDeletionCancelledMail($trooper));

        return null;
    }
}
