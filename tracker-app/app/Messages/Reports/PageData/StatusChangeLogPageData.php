<?php

declare(strict_types=1);

namespace App\Messages\Reports\PageData;

use App\Enums\EventTrooperStatus;
use App\Messages\Reports\Queries\GetStatusChangeLog;
use App\Messages\Reports\Resources\StatusChangeRowResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * Data for the Status Change Log report: troops marked attended by someone else, last 30 days.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class StatusChangeLogPageData extends Message
{
    public const DAYS = 30;

    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $changes = GetStatusChangeLog::call(moderator: $this->actor, lookback: self::DAYS);

        return [
            'caption' => __('admin.Troopers who :attended an event where their status was updated by someone other than the trooper in action for the last :days days.', [
                'attended' => EventTrooperStatus::ATTENDED->label(),
                'days' => self::DAYS,
            ]),
            'changes' => StatusChangeRowResource::collection($changes->values()),
        ];
    }
}
