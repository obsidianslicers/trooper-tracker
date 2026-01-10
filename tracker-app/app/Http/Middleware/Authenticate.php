<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

/**
 * Custom authentication middleware for trooper authentication.
 *
 * Extends Laravel's base authentication middleware to provide custom
 * redirection behavior when a trooper is not authenticated.
 */
class Authenticate extends Middleware
{
    /**
     * Get the path the trooper should be redirected to when they are not authenticated.
     *
     * Returns the login route URL for non-JSON requests, or null for JSON requests
     * (which will receive a 401 response instead of a redirect).
     *
     * @param Request $request The incoming HTTP request
     * @return string|null The login route URL or null for JSON requests
     */
    protected function redirectTo(Request $request): ?string
    {
        if (!$request->expectsJson())
        {
            return route('auth.login');
        }

        return null;
    }
}