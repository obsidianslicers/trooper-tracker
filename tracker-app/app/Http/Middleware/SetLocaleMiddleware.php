<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Trooper;
use App\Services\TranslationService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Uses the signed-in trooper's language when they picked one, otherwise the site default.
 */
class SetLocaleMiddleware
{
    public function __construct(private readonly TranslationService $translations) {}

    public function handle(Request $request, Closure $next): Response
    {
        $trooper = $request->user();

        $locale = $trooper instanceof Trooper
            ? $trooper->preferredLocale()
            : null;

        App::setLocale($locale ?? $this->translations->siteLocale());

        return $next($request);
    }
}
