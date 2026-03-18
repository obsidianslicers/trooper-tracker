<?php

use App\Jobs\SendExceptionNotificationJob;
use App\Jobs\UpdateEventForumThreadJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Exceptions\InvalidSignatureException;
use Illuminate\Support\Facades\Cache;

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
            __DIR__ . '/../routes/web/admin-reports.php',
            __DIR__ . '/../routes/web/service-records.php',
        ],
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void
    {
        $schedule->job(new UpdateEventForumThreadJob)->everyMinute();

        $schedule->command('tracker:synchronize-xenforo-users')->hourly();
    })
    ->withMiddleware(function (Middleware $middleware): void
    {
        $middleware->web(append: [
            \App\Http\Middleware\FlashMessageMiddleware::class,
            \App\Http\Middleware\HtmxDispatchHeaderMiddleware::class,
            \App\Http\Middleware\UpdateLastActiveMiddleware::class,
            \App\Http\Middleware\TrooperSetupRequiredMiddleware::class,
            \App\Http\Middleware\EnsureXenforoLinked::class,
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
        $exceptions->reportable(function (Throwable $e)
        {
            if (app()->environment('production', 'prod', 'prd') && method_exists(Cache::getFacadeRoot(), 'throttle'))
            {
                if (Cache::throttle('exception-email')->allow(1)->every(60)->hit())
                {
                    $context = [
                        'url' => request()->fullUrl(),
                        'method' => request()->method(),
                        'user_id' => optional(auth()->user())->id,
                        'ip' => request()->ip(),
                        'input' => request()->except(['password', 'password_confirmation', '_token',]),
                    ];

                    dispatch(new SendExceptionNotificationJob($e, $context));
                }
            }
        });

        $exceptions->render(function (InvalidSignatureException $e, Request $request)
        {
            return redirect()->route('verification.notice')->with('status', 'invalid');
        });
    })->create();
