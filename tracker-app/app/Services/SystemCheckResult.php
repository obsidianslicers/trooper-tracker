<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\SystemCheckStatus;

class SystemCheckResult
{
    public function __construct(
        public readonly string $label,
        public readonly SystemCheckStatus $status,
        public readonly ?string $detail = null,
    ) {}
}
