<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Sends the trooper a new link to verify their email.
 *
 * @method static void call(Trooper $trooper)
 */
final class SendTrooperVerificationEmail extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    public function handle(): void
    {
        $this->trooper->sendEmailVerificationNotification();
    }
}
