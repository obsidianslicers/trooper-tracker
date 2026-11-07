<?php

declare(strict_types=1);

namespace App\Mail;

use Closure;
use Illuminate\Mail\SendQueuedMailable;

/**
 * Queue middleware for queued mail: while the OutgoingMailBudget is spent, put the job
 * back until there is room instead of sending it.
 */
final class ThrottleOutgoingMail
{
    /**
     * Released jobs wake at random within this many seconds after there is room, so a
     * big backlog (an event announcement to every trooper) doesn't all wake at once.
     */
    private const SPREAD_SECONDS = 600;

    public function handle(object $job, Closure $next): mixed
    {
        $wait = app(OutgoingMailBudget::class)->secondsUntilAvailable($this->recipients($job));

        if ($wait === 0)
        {
            return $next($job);
        }

        $job->release($wait + random_int(0, self::SPREAD_SECONDS));

        return null;
    }

    private function recipients(object $job): int
    {
        if ($job instanceof SendQueuedMailable)
        {
            $mailable = $job->mailable;

            return max(1, count($mailable->to) + count($mailable->cc) + count($mailable->bcc));
        }

        // a queued notification is one job per notifiable
        return 1;
    }
}
