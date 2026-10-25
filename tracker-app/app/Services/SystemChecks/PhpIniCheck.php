<?php

declare(strict_types=1);

namespace App\Services\SystemChecks;

use App\Enums\SystemCheckStatus;
use App\Services\SystemCheckResult;

class PhpIniCheck
{
    private const UNLIMITED_WHEN_ZERO = ['max_execution_time', 'max_input_time'];

    /**
     * Compare php.ini values against the configured targets.
     *
     * @param  array<string, string>  $actual
     */
    public function check(array $actual, string $label): SystemCheckResult
    {
        $below = [];
        $values = [];

        foreach ((array) config('tracker.system_check.php_ini', []) as $setting => $target)
        {
            $value = (string) ($actual[$setting] ?? '');
            $values[] = "{$setting}={$value}";

            if ($this->isBelow($setting, $value, (string) $target))
            {
                $below[] = "{$setting}={$value} (target {$target})";
            }
        }

        if ($below !== [])
        {
            return new SystemCheckResult($label, SystemCheckStatus::WARN, 'Below target: '.implode(', ', $below));
        }

        return new SystemCheckResult($label, SystemCheckStatus::PASS, implode(', ', $values));
    }

    private function isBelow(string $setting, string $value, string $target): bool
    {
        if ($value === '0' && in_array($setting, self::UNLIMITED_WHEN_ZERO, true))
        {
            return false;
        }

        $actual_number = IniValue::toNumber($value);

        return $actual_number !== null && $actual_number < (int) IniValue::toNumber($target);
    }
}
