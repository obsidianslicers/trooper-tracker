<?php

declare(strict_types=1);

namespace App\Services\SystemChecks;

use App\Enums\SystemCheckStatus;
use App\Services\SystemCheckResult;

class DiskSpaceCheck
{
    public function check(string $label, float|int|null $free, float|int|null $total): SystemCheckResult
    {
        if (empty($free) || empty($total))
        {
            return new SystemCheckResult($label, SystemCheckStatus::WARN, 'Disk space could not be read');
        }

        $percent = (int) round(($free / $total) * 100);
        $detail = $this->toGigabytes($free).' GB free of '.$this->toGigabytes($total)." GB ({$percent}%)";
        $low = $percent < (int) config('tracker.system_check.disk_warn_percent', 10)
            || $free < (int) config('tracker.system_check.disk_warn_bytes', 2 * 1024 ** 3);

        return new SystemCheckResult($label, $low ? SystemCheckStatus::WARN : SystemCheckStatus::PASS, $detail);
    }

    private function toGigabytes(float|int $bytes): string
    {
        return number_format($bytes / 1024 ** 3, 1);
    }
}
