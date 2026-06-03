<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

readonly class CancelAccountDeletionCommand
{
    public function __construct(public Trooper $trooper) {}
}
