<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use App\Models\Trooper;

/**
 * Who may link trackers. Minors (an account with a guardian, or under 18 by date
 * of birth) can't, on either side.
 */
final class LinkEligibility
{
    public static function canLink(Trooper $trooper): bool
    {
        if ($trooper->guardian_id !== null)
        {
            return false;
        }

        $date_of_birth = $trooper->date_of_birth;

        return $date_of_birth === null || $date_of_birth->lte(now()->subYears(18));
    }
}
