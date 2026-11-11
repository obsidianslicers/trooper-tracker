<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Sentry\Listeners;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Arr;
use Sentry\State\Scope;
use Throwable;

use function Sentry\captureException;
use function Sentry\withScope;

/**
 * Sends an exception to Sentry when it is logged at error level or above.
 *
 * Core's exception handler logs every unhandled 5xx with Log::error(..., ['exception' => $e])
 * and then stops Laravel's other reporters, so the log is where the plugin sees them. Plugin
 * failures, failed jobs and anything else passed to report() arrive the same way.
 */
final class ReportLoggedException
{
    private const LEVELS = ['error', 'critical', 'alert', 'emergency'];

    /** Set while sending, so a failure that is itself logged isn't sent in a loop. */
    private static bool $reporting = false;

    public function handle(MessageLogged $logged): void
    {
        $exception = $logged->context['exception'] ?? null;

        if (self::$reporting
            || !$exception instanceof Throwable
            || !in_array($logged->level, self::LEVELS, true))
        {
            return;
        }

        self::$reporting = true;

        try
        {
            withScope(function (Scope $scope) use ($logged, $exception): void
            {
                //  ids only: names, emails and form input stay out of Sentry
                $user_id = $logged->context['user_id'] ?? null;

                if ($user_id !== null)
                {
                    $scope->setUser(['id' => (string) $user_id]);
                }

                $scope->setExtras(Arr::only($logged->context, ['status_code', 'method']));

                captureException($exception);
            });
        }
        finally
        {
            self::$reporting = false;
        }
    }
}
