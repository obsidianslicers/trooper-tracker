<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PluginChangeAction;
use App\Models\Base\PluginChange as BasePluginChange;
use App\Models\Concerns\HasTrooperStamps;

/**
 * One entry in a plugin's change history: installs, upgrades, enables, failures
 * and rollbacks, with the versions and migrations involved.
 */
class PluginChange extends BasePluginChange
{
    use HasTrooperStamps;

    protected function casts(): array
    {
        return [
            self::ACTION => PluginChangeAction::class,
        ];
    }

    /**
     * @param  array<int, string>  $migrations
     */
    public static function record(
        string $slug,
        PluginChangeAction $action,
        ?string $from_version = null,
        ?string $to_version = null,
        array $migrations = [],
        ?string $message = null,
    ): self {
        return static::query()->create([
            self::SLUG => $slug,
            self::ACTION => $action,
            self::FROM_VERSION => $from_version,
            self::TO_VERSION => $to_version,
            self::MIGRATIONS => $migrations === [] ? null : $migrations,
            self::MESSAGE => $message,
        ]);
    }
}
