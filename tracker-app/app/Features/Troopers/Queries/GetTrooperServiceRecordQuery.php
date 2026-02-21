<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

final readonly class GetTrooperServiceRecordQuery
{
    public function __construct(
        public int $trooper_id
    ) {}
}
