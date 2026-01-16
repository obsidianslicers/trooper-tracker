<?php

namespace App\Http\Controllers;

use App\Bus\MagicBus;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected readonly MagicBus $bus,
    ) {
    }
}
