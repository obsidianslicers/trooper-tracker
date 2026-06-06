<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Http\HasMessageDispatcher;
use App\Messages\App\Queries\GetConfig;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    use HasMessageDispatcher;

    /**
     * The root template loaded on the first Inertia page visit.
     */
    protected $rootView = 'layouts.base';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $config = $this->dispatchMessage($request, GetConfig::class);

        return [
            ...parent::share($request),
            'config' => $config,
        ];
    }
}
