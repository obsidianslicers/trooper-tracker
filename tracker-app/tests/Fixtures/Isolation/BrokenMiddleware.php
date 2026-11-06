<?php

declare(strict_types=1);

namespace Tests\Fixtures\Isolation;

use Closure;
use Illuminate\Http\Request;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

class BrokenMiddleware
{
    public static string $mode = 'throw';

    public function handle(Request $request, Closure $next): Response
    {
        return match (self::$mode)
        {
            'abort' => abort(403),
            'pass' => $next($request),
            default => throw new RuntimeException('The forum is down.'),
        };
    }
}
