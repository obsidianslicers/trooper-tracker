<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * How a deploy treats a Composer plugin package that is already installed.
 */
enum PackageUpdatePolicy: string
{
    use HasEnumHelpers;

    /** Keep the installed version. */
    case PINNED = 'pinned';

    /** Take newer versions with the same major version (no breaking changes). */
    case COMPATIBLE = 'compatible';

    public function description(): string
    {
        return match ($this)
        {
            self::PINNED => 'Keep this version',
            self::COMPATIBLE => 'Compatible updates on each deploy',
        };
    }
}
