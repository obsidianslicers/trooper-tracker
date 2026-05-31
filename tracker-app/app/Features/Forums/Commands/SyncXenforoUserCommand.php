<?php

declare(strict_types=1);

namespace App\Features\Forums\Commands;

use App\Models\Trooper;

readonly class SyncXenforoUserCommand
{
    public function __construct(public Trooper $trooper) {}
}
