<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Traits;

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

    /**
     * The name of the event type a legacy event label maps to. Every name here is one
     * of the types the create_event_types migration seeds.
     */
    public static function eventLabelFromLegacyId(int $id): string
    {
        return match ($id)
        {
            0 => 'Regular',
            10 => 'Armor Party',
            1 => 'Charity',
            2 => 'Public Relations',
            3 => 'Disney',
            11 => 'Lucas Film Limited',
            4 => 'Convention',
            9 => 'Hospital',
            5 => 'Wedding',
            6 => 'Birthday Party',
            7 => 'Virtual Troop',
            8 => 'Other',
            default => 'Other',
        };
    }

}