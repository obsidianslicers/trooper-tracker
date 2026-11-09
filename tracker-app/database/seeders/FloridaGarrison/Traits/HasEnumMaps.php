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
     * The slug of the event type a legacy event label maps to. Every slug here is one
     * of the types the create_event_types migration seeds.
     */
    public static function eventLabelFromLegacyId(int $id): string
    {
        return match ($id)
        {
            0 => 'regular',
            10 => 'armorparty',
            1 => 'charity',
            2 => 'publicrelations',
            3 => 'disney',
            11 => 'lucasfilm',
            4 => 'convention',
            9 => 'hospital',
            5 => 'wedding',
            6 => 'birthdayparty',
            7 => 'virtualtroop',
            8 => 'other',
            default => 'other',
        };
    }

}