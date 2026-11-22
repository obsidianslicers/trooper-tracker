<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventGuestStatus;
use App\Enums\EventTrooperStatus;

/**
 * A sign-up's status as the event page shows it: its value, label and text colour.
 */
final class EventDisplayStatus
{
    /**
     * @return array{value: string, label: string, color: string}
     */
    public static function of(EventTrooperStatus|EventGuestStatus $status): array
    {
        return [
            'value' => $status->value,
            'label' => $status->label(),
            'color' => $status->color(),
        ];
    }
}
