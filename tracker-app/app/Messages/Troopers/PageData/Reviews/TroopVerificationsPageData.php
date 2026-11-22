<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Reviews;

use App\Messages\Events\Queries\Verification\GetTroopsAwaitingVerification;
use App\Messages\Events\Resources\Verification\TroopVerificationRowResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The troops waiting for the actor to confirm which clubs get credit.
 *
 * @method static array call(Actor $actor)
 */
final class TroopVerificationsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        return [
            'event_troopers' => TroopVerificationRowResource::collection(
                GetTroopsAwaitingVerification::call(moderator: $this->actor)
            ),
        ];
    }
}
