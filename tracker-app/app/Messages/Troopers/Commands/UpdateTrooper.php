<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Command to update trooper profile information.
 *
 * Updates the trooper model with validated data and optionally marks
 * the trooper's initial setup as completed by setting setup_completed_at.
 *
 * @method static void call(Trooper $trooper, array $valid_data, bool $complete_setup = false)
 */
final class UpdateTrooper extends Message
{
    /**
     * Create a new command instance.
     *
     * @param  Trooper  $trooper  The trooper to update
     * @param  array<string, mixed>  $valid_data  Validated attributes to update on the trooper
     * @param  bool  $complete_setup  Whether to mark setup as completed (default: false)
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $valid_data,
        private readonly bool $complete_setup = false) {}

    /**
     * Execute the command to update trooper profile.
     *
     * @return null
     */
    public function handle(): void
    {
        $this->trooper->fill($this->valid_data);

        if ($this->complete_setup)
        {
            $this->trooper->setup_completed_at = now();
        }

        $this->trooper->save();
    }
}
