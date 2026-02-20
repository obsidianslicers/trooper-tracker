<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

/**
 * Command to recalculate trooper rank and achievements.
 *
 * Recalculates a trooper's rank based on their event attendance and updates
 * all achievement records including rank, shift count, volunteer hours,
 * charity funds, and milestone achievements.
 *
 * When trooper_id is null, processes all troopers in the system.
 * When trooper_id is provided, processes only that specific trooper.
 *
 * @see RecalculateTrooperRankCommandHandler
 */
readonly class RecalculateTrooperRankCommand
{
    /**
     * Create a new command instance.
     *
     * @param  int|null  $trooper_id  The ID of the trooper to recalculate rank for, or null to recalculate all troopers
     */
    public function __construct(public readonly ?int $trooper_id = null) {}
}
