<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

readonly class UpdateTrooperCommand
{

    public function __construct(
        public Trooper $trooper,
        public array $valid_data,
        public bool $complete_setup = false)
    {
    }
}

