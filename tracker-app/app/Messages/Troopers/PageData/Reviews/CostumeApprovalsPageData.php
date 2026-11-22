<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Reviews;

use App\Messages\Troopers\Queries\Costumes\GetPendingTrooperCostumes;
use App\Messages\Troopers\Resources\PendingCostumeRowResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The costumes troopers added themselves that the actor may approve.
 *
 * @method static array call(Actor $actor)
 */
final class CostumeApprovalsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'trooper_costumes' => PendingCostumeRowResource::collection(
                GetPendingTrooperCostumes::call(moderator: $this->actor)
            ),
        ];
    }
}
