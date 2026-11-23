<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Auth\Events\Verified;

/**
 * Marks the trooper's email as verified, once.
 *
 * @method static void call(Trooper $trooper)
 */
final class VerifyTrooperEmail extends Message
{
    public function __construct(private readonly Trooper $trooper) {}

    public function handle(): void
    {
        if ($this->trooper->hasVerifiedEmail())
        {
            return;
        }

        $this->trooper->markEmailAsVerified();

        event(new Verified($this->trooper));
    }
}
