<?php

declare(strict_types=1);

namespace Database\Seeders\Conversions\Traits;

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

}