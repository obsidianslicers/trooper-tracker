<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Plugins\PluginGuard;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

/**
 * Wraps a plugin's web middleware so an error in it lets the request carry on
 * without that middleware, instead of failing the page. Errors from the rest of
 * the request, after the plugin's middleware passed it on, are not caught here.
 *
 * Used as "IsolatePluginMiddleware:App\Plugin\Middleware".
 */
class IsolatePluginMiddleware
{
    /**
     * Thrown on purpose to answer the request, such as abort(403).
     */
    private const RESPONSES = [
        HttpExceptionInterface::class,
        HttpResponseException::class,
        AuthenticationException::class,
        AuthorizationException::class,
    ];

    public function handle(Request $request, Closure $next, string $middleware_class): Response
    {
        $passed_on = false;
        $guarded_next = function (Request $request) use ($next, &$passed_on): mixed
        {
            $passed_on = true;

            return $next($request);
        };

        try
        {
            return app($middleware_class)->handle($request, $guarded_next);
        }
        catch (Throwable $e)
        {
            if ($passed_on || $this->isResponse($e))
            {
                throw $e;
            }

            PluginGuard::failed("run middleware [{$middleware_class}]", $e);

            return $next($request);
        }
    }

    private function isResponse(Throwable $e): bool
    {
        foreach (self::RESPONSES as $class)
        {
            if ($e instanceof $class)
            {
                return true;
            }
        }

        return false;
    }
}
