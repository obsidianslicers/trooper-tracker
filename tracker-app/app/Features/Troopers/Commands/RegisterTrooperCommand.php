<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to register a new trooper.
 *
 * Creates a new trooper model with validated data and optionally marks
 * the trooper's initial setup as completed by setting setup_completed_at.
 *
 * @see RegisterTrooperCommandHandler
 */
readonly class RegisterTrooperCommand
{
    /**
     * Create a new command instance.
     *
     * @param  array<string, mixed>  $valid_data  Validated attributes to update on the trooper
     */
    public function __construct(public array $valid_data) {}
}
