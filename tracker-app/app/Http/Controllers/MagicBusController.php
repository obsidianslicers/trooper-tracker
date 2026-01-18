<?php

namespace App\Http\Controllers;

use App\Bus\MagicBus;
use App\Services\BreadCrumbService;
use App\Services\FlashMessageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class MagicBusController
{
    use AuthorizesRequests;

    public function __construct(
        protected readonly MagicBus $bus,
        protected readonly FlashMessageService $flash,
        protected readonly BreadCrumbService $crumbs,
    ) {
        $this->initialized();
    }

    protected function initialized()
    {
    }
}
