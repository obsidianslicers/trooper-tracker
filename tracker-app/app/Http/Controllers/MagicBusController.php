<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Bus\MagicBus;
use App\Services\BreadCrumbService;
use App\Services\FlashMessageService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Base controller providing MagicBus integration and common services.
 *
 * This abstract controller serves as the foundation for all application controllers,
 * providing dependency injection for MagicBus, flash messaging, and breadcrumb services.
 * Extends the functionality with authorization capabilities and an initialization hook.
 */
abstract class MagicBusController
{
    use AuthorizesRequests;

    /**
     * Create a new controller instance with core services.
     *
     * @param  MagicBus  $bus  The command/query bus for domain operations
     * @param  FlashMessageService  $flash  The flash messaging service
     * @param  BreadCrumbService  $crumbs  The breadcrumb navigation service
     */
    public function __construct(
        protected readonly MagicBus $bus,
        protected readonly FlashMessageService $flash,
        protected readonly BreadCrumbService $crumbs,
    ) {
        $this->initialized();
    }

    /**
     * Hook for controller initialization logic.
     *
     * Override this method in child controllers to add initialization logic
     * such as setting breadcrumbs or preparing shared view data. This is called
     * after dependency injection is complete.
     */
    protected function initialized(): void {}
}
