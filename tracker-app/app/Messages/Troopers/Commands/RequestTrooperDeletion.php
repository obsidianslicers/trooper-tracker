<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Mail\Account\AccountDeletionRequestedMail;
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
final class RequestTrooperDeletion extends Message
{
    public function __construct(private readonly Trooper $trooper)
    {
    }

    public function handle(): void
    {
        // Queue the confirmation email before wiping PII — email address is still valid here
        Mail::to($this->trooper->email)->queue(new AccountDeletionRequestedMail($this->trooper));

        $this->trooper->deletion_requested_at = now();
        $this->trooper->save();
    }
}