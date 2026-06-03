<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Mail\Account\AccountDeletionRequestedMail;
use Illuminate\Support\Facades\Mail;

/**
 * @implements CommandHandlerInterface<RequestAccountDeletionCommand>
 */
readonly class RequestAccountDeletionCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    /**
     * @param  RequestAccountDeletionCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $trooper = $message->trooper;

        // Queue the confirmation email before wiping PII — email address is still valid here
        Mail::to($trooper->email)->queue(new AccountDeletionRequestedMail($trooper));

        $trooper->forceFill(['deletion_requested_at' => now()])->save();

        return null;
    }
}
