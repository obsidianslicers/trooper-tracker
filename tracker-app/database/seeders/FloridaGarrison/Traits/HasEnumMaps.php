<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Traits;

use App\Enums\EventType;
use App\Enums\MembershipStatus;

trait HasEnumMaps
{
    protected function mapLegacyStatus(int $value): string
    {
        return match ((int) $value)
        {
            0 => MembershipStatus::NONE->value,
            1 => MembershipStatus::ACTIVE->value,
            2 => MembershipStatus::RESERVE->value,
            3 => MembershipStatus::RETIRED->value,
            4 => MembershipStatus::ACTIVE->value,   //HANDLER
            default => MembershipStatus::NONE->value, // fallback
        };
    }

    public static function eventLabelFromLegacyId(int $id): EventType
    {
        return match ($id)
        {
            0 => EventType::REGULAR,
            10 => EventType::ARMOR_PARTY,
            1 => EventType::CHARITY,
            2 => EventType::PUBLIC_RELATIONS,
            3 => EventType::DISNEY,
            11 => EventType::LUCAS_FILM_LIMITED,
            4 => EventType::CONVENTION,
            9 => EventType::HOSPITAL,
            5 => EventType::WEDDING,
            6 => EventType::BIRTHDAY_PARTY,
            7 => EventType::VIRTUAL_TROOP,
            8 => EventType::OTHER,
            default => EventType::OTHER,
        };
    }

}