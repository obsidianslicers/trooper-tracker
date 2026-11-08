<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * An administrator removes the "do not trust" flag. Troops already waiting for
 * verification stay in the queue.
 */
final class ClearTrooperUntrusted extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
    ) {}

    public function handle(): void
    {
        $this->trooper->is_untrusted = false;
        $this->trooper->untrusted_reason = null;
        $this->trooper->untrusted_at = null;
        $this->trooper->untrusted_id = null;
        $this->trooper->save();
    }
}
