<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\ExceptionOccurred;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Sends exception notification emails to all administrator troopers.
 *
 * This job follows the **Orchestration Pattern** - it coordinates service calls
 * to notify administrators when critical exceptions occur in the application.
 *
 * Workflow:
 * 1. Retrieves all administrator troopers via GetTroopersByRoleQuery
 * 2. Queues ExceptionOccurred email to each administrator
 * 3. Emails include exception details, stack trace, and context for debugging
 *
 * This ensures administrators are promptly notified of system issues without
 * blocking the application flow. Emails are queued asynchronously to prevent
 * cascading failures if the mail system has issues.
 *
 * Typical usage:
 * - Unhandled exceptions caught by global exception handler
 * - Critical errors requiring immediate admin attention
 * - System integrity issues detected during operations
 */
class SendExceptionNotificationJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new exception notification job instance.
     *
     * @param  Throwable  $exception  The exception that occurred in the application.
     * @param  array<string, mixed>  $context  Additional context about when/where exception occurred (request, user, etc.).
     */
    public function __construct(
        private readonly Throwable $exception,
        private readonly array $context = []) {}

    /**
     * Execute the job to send exception notifications.
     *
     * Retrieves all administrator troopers and queues an exception notification
     * email to each one. Uses Mail::queue() for asynchronous delivery.
     */
    public function handle(MagicBus $bus): void
    {
        $admin_query = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);

        $admins = $bus->send($admin_query);

        foreach ($admins as $admin)
        {
            Mail::to($admin->email)->queue(new ExceptionOccurred($admin, $this->exception, $this->context));
        }
    }
}
