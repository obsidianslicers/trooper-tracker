<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\BreadCrumbService;

/**
 * Base controller for pages, providing breadcrumbs and an initialization hook.
 */
abstract class PageController extends Controller
{
    /**
     * Create a new controller instance with core services.
     *
     * @param  BreadCrumbService  $crumbs  The breadcrumb navigation service
     */
    public function __construct(
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
