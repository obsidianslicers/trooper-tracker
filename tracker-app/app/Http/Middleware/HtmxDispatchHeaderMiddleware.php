<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware to preserve HTMX dispatch headers across requests.
 *
 * This middleware copies the X-Dispatch-ID header from the request to the response,
 * allowing HTMX to properly track and handle custom event dispatches.
 */
class HtmxDispatchHeaderMiddleware
{
    /**
     * Handle an incoming request and preserve HTMX dispatch headers.
     *
     * If the request contains an X-Dispatch-ID header, this method copies it
     * to the response to ensure HTMX event tracking continues properly.
     *
     * @param Request $request The incoming HTTP request
     * @param Closure $next The next middleware in the pipeline
     * @return Response The HTTP response with preserved dispatch headers
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->hasHeader('X-Dispatch-ID'))
        {
            $response->headers->set('X-Dispatch-ID', $request->header('X-Dispatch-ID'));
        }

        return $response;

    }
}
