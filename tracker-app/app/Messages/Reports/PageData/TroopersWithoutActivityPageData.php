<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetTroopersWithoutActivity;
use App\Messages\Reports\Resources\InactiveTrooperRowResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Troopers Without Activity report: active troopers with no troop in 12 months.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class TroopersWithoutActivityPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $lookback = now()->subMonths(12);
        $troopers = GetTroopersWithoutActivity::call(moderator: $this->actor, lookback: $lookback);

        return [
            'caption' => __('admin.Active Troopers who have not :attended an event since :date.', [
                'attended' => EventTrooperStatus::ATTENDED->label(),
                'date' => $lookback->localFormat('M d, Y'),
            ]),
            'troopers' => InactiveTrooperRowResource::collection($troopers->values()),
        ];
    }
}
