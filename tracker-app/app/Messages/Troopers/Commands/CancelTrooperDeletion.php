<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Mail\Account\AccountDeletionCancelledMail;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Facades\Mail;

/**
 * Handles the request for deletion of a trooper's account.
 * This command is responsible for marking the trooper's account as deletion requested
 * and sending a confirmation email.
 *
 * @method static void call(Trooper $trooper)
 */
final class CancelTrooperDeletion extends Message
{
    public function __construct(private readonly Trooper $trooper)
    {
    }

    public function handle(): void
    {
        $this->trooper->deletion_requested_at = null;
        $this->trooper->save();

        Mail::to($this->trooper->email)->queue(new AccountDeletionCancelledMail($this->trooper));
    }
}
