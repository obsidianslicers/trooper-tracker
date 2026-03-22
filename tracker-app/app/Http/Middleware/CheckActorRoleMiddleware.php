<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use ValueError;

/**
 * Middleware to verify that the authenticated trooper has the required membership role.
 *
 * This middleware checks if the authenticated user has an active membership with one of
 * the specified roles. Returns 401 if not authenticated, 403 if authenticated but without
 * the required role.
 */
class CheckActorRoleMiddleware
{
    /**
     * Handle an incoming request and verify trooper role authorization.
     *
     * Checks if the authenticated trooper has an active membership with one of the
     * specified roles. Roles can be passed as string values that will be converted
     * to MembershipRole enums.
     *
     * @param  Request  $request  The incoming HTTP request
     * @param  Closure  $next  The next middleware in the pipeline
     * @param  string  ...$roles  Variable number of role names to check against
     * @return Response The response from the next middleware if authorized
     *
     * @throws InvalidArgumentException If an invalid role string is provided
     * @throws HttpException If not authenticated (401) or unauthorized (403)
     */
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (count($roles) > 0)
        {
            $user = Auth::user();

            if (!$user)
            {
                abort(401, 'Unauthorized.');
            }

            foreach ($roles as $role)
            {
                if (is_string($role))
                {
                    try
                    {
                        $role = MembershipRole::from($role);
                    }
                    catch (ValueError $e)
                    {
                        throw new InvalidArgumentException("Invalid MembershipRole: '{$role}'");
                    }
                }

                if ($user->membership_status == MembershipStatus::ACTIVE && $user->membership_role === $role)
                {
                    return $next($request);
                }
            }

            //  don't match any role - now - do we?!
            abort(403, 'Unauthorized action.');
        }

        return $next($request);
    }
}
