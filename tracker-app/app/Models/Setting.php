<?php

namespace App\Models;

use App\Models\Base\Setting as BaseSetting;
use App\Models\Casts\LowerCast;
use App\Models\Concerns\HasTrooperStamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Represents an application configuration setting.
 *
 * Settings are key-value pairs that store application-wide configuration
 * options that can be modified at runtime. Examples include feature flags,
 * system messages, and configurable thresholds.
 */
class Setting extends BaseSetting
{
    use HasFactory;
    use HasTrooperStamps;

    protected function casts()
    {
        return array_merge($this->casts, [
            self::KEY => LowerCast::class
        ]);
    }
}
