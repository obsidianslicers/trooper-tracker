<?php

declare(strict_types=1);

namespace App\Features\Troopers\Commands;

use App\Models\Trooper;

readonly class UnvoidTrooperCommand
{
    public function __construct(public Trooper $trooper) {}
}
