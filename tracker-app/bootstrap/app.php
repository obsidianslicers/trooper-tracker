<?php

use App\Jobs\SendExceptionNotificationJob;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Support\Facades\RateLimiter;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: [
            __DIR__ . '/../routes/web/home.php',
            __DIR__ . '/../routes/web/shares.php',
            __DIR__ . '/../routes/web/events.php',
            __DIR__ . '/../routes/web/auth.php',
            __DIR__ . '/../routes/web/verification.php',
            __DIR__ . '/../routes/web/widgets.php',
            __DIR__ . '/../routes/web/pickers.php',
            __DIR__ . '/../routes/web/account.php',
            __DIR__ . '/../routes/web/admin.php',
            __DIR__ . '/../routes/web/admin-organizations.php',
            __DIR__ . '/../routes/web/admin-costumes.php',
            __DIR__ . '/../routes/web/admin-notices.php',
            __DIR__ . '/../routes/web/admin-awards.php',
            __DIR__ . '/../routes/web/admin-events.php',
            __DIR__ . '/../routes/web/admin-troopers.php',
            __DIR__ . '/../routes/web/admin-faq.php',
            __DIR__ . '/../routes/web/admin-reports.php',
            __DIR__ . '/../routes/web/service-records.php',
            __DIR__ . '/../routes/web/search.php',
            __DIR__ . '/../routes/web/api.php',
            __DIR__ . '/../routes/web/mobile-api.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void
    {
        $middleware->validateCsrfTokens(except: [
            'api/push-notifications',
            'api/push-notifications/*',
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \App\Http\Middleware\FlashMessageMiddleware::class,
            \App\Http\Middleware\PushNotificationCountMiddleware::class,
            \App\Http\Middleware\HtmxDispatchHeaderMiddleware::class,
            \App\Http\Middleware\UpdateLastActiveMiddleware::class,
            \App\Http\Middleware\TrooperSetupRequiredMiddleware::class,
            \App\Http\Middleware\CheckVisitorAccessMiddleware::class,
            \App\Http\Middleware\EnsureXenforoLinked::class,
            \App\Http\Middleware\CheckXenforoBanMiddleware::class,
        ]);

        $middleware->alias([
            'check.role' => \App\Http\Middleware\CheckActorRoleMiddleware::class,
            'check.active' => \App\Http\Middleware\CheckActiveTrooperMiddleware::class,
            'auth.registration' => \App\Http\Middleware\RegistrationMiddleware::class,
        ]);

        $middleware->redirectGuestsTo(fn(Illuminate\Http\Request $request) => route('auth.login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void
    {
        $exceptions->reportable(function (Throwable $e): void
        {
            if (!app()->environment('production', 'prod', 'prd'))
            {
                return;
            }

            $status_code = $e instanceof HttpExceptionInterface
                ? $e->getStatusCode()
                : 500;

            if ($status_code < 500)
            {
                return;
            }

            if (!(bool) config('tracker.exception_notifications.enabled', true))
            {
                return;
            }

            $max_attempts = 1;
            $delay = 60;
            $callback = function () use ($e, $status_code)
            {
                $context = [
                    'status_code' => $status_code,
                    'url' => request()->fullUrl(),
                    'method' => request()->method(),
                    'user_id' => optional(auth()->user())->id,
                    'ip' => request()->ip(),
                    'input' => request()->except(['password', 'password_confirmation', '_token']),
                ];

                dispatch(new SendExceptionNotificationJob($e, $context));
            };

            RateLimiter::attempt('exception-email', $max_attempts, $callback, $delay);
        });

        $exceptions->render(function (InvalidSignatureException $e, Request $request)
        {
            return redirect()->route('verification.notice')->with('status', 'invalid');
        });

        $exceptions->render(function (PostTooLargeException $e, Request $request)
        {
            if (!$request->isHtmx())
            {
                return null;
            }

            $message = json_encode([
                'message' => 'That upload is too large for the server. Please upload JPG, PNG, or WEBP images that are 12MB or smaller.',
                'type' => 'danger',
                'fadeOut' => 5000,
            ]);

            return response('', 413)->header('X-Flash-Message', $message);
        });
    })->create();
