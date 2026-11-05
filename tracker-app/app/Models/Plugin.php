<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Base\Plugin as BasePlugin;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Builder;

/**
 * The stored state of one plugin: whether it is enabled, and its settings.
 *
 * Settings are encrypted at rest because plugins often store webhook URLs and
 * API keys there.
 */
class Plugin extends BasePlugin
{
    use HasTrooperStamps;

    protected function casts(): array
    {
        return [
            self::SETTINGS => 'encrypted:array',
        ];
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where(self::IS_ENABLED, true);
    }
}
