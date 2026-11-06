<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Support;

use Carbon\Carbon;
use Throwable;
use TroopTracker\Plugins\Holonet\Models\LinkedTroop;

/**
 * Turns one troop sent by another tracker into the columns stored here,
 * dropping anything that isn't a well-formed troop.
 */
final class TroopRecord
{
    /**
     * @return array<string, mixed>|null
     */
    public static function toAttributes(mixed $troop): ?array
    {
        if (!is_array($troop))
        {
            return null;
        }

        $id = self::text($troop['id'] ?? null, 64);
        $name = self::text($troop['event_name'] ?? null, 256);
        $starts_at = self::time($troop['shift_starts_at'] ?? null);
        $ends_at = self::time($troop['shift_ends_at'] ?? null);

        if ($id === null || $name === null || $starts_at === null || $ends_at === null
            || $ends_at->lt($starts_at))
        {
            return null;
        }

        return [
            LinkedTroop::REMOTE_ID => $id,
            LinkedTroop::EVENT_NAME => $name,
            LinkedTroop::EVENT_TYPE => self::text($troop['event_type'] ?? null, 32),
            LinkedTroop::VENUE => self::text($troop['venue'] ?? null, 256),
            LinkedTroop::SHIFT_STARTS_AT => $starts_at,
            LinkedTroop::SHIFT_ENDS_AT => $ends_at,
            LinkedTroop::COSTUME_NAME => self::text($troop['costume'] ?? null, 256),
            LinkedTroop::CLUBS => self::clubs($troop['clubs'] ?? null),
            LinkedTroop::IS_HANDLER => ($troop['is_handler'] ?? false) === true,
        ];
    }

    private static function text(mixed $value, int $length): ?string
    {
        if (!is_string($value) && !is_int($value))
        {
            return null;
        }

        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : mb_substr($value, 0, $length);
    }

    private static function time(mixed $value): ?Carbon
    {
        if (!is_string($value) || $value === '')
        {
            return null;
        }

        try
        {
            return Carbon::parse($value)->setTimezone(config('app.timezone'));
        }
        catch (Throwable)
        {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    private static function clubs(mixed $clubs): array
    {
        if (!is_array($clubs))
        {
            return [];
        }

        return collect($clubs)
            ->map(fn (mixed $club): ?string => self::text($club, 64))
            ->filter()
            ->unique()
            ->take(10)
            ->values()
            ->all();
    }
}
