<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData;

use App\Messages\Events\Queries\GetTroopsToConfirm;
use App\Messages\Events\Resources\TroopToConfirmResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the "Troops to confirm" page.
 *
 * @method static array<string, mixed> call(Actor&Trooper $actor)
 */
final class ListTroopsToConfirmPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
    ) {}

    public function handle(): array
    {
        return [
            'troops' => TroopToConfirmResource::collection(GetTroopsToConfirm::call(trooper: $this->actor)),
        ];
    }
}
