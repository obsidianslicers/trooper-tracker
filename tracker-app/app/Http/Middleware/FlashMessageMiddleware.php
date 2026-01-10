<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\FlashMessageService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Middleware to share flash message service with all views.
 *
 * This middleware makes the FlashMessageService available to all Blade templates
 * by sharing it through the View facade, enabling consistent flash message handling
 * across the application.
 */
class FlashMessageMiddleware
{
    /**
     * Create a new flash message middleware instance.
     *
     * @param FlashMessageService $flash The flash message service to share with views
     */
    public function __construct(
        protected readonly FlashMessageService $flash
    ) {
    }

    /**
     * Handle an incoming request and share flash messages with views.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return mixed The response from the next middleware
     */
    public function handle(Request $request, Closure $next)
    {
        View::share('flash', $this->flash);

        return $next($request);
    }
}