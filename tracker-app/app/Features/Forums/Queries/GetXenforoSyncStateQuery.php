<?php

declare(strict_types=1);

namespace App\Features\Forums\Queries;

use App\Models\Trooper;

readonly class GetXenforoSyncStateQuery
{
    public function __construct(public Trooper $trooper) {}
}
