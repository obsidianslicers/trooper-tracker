<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Bus\MagicBus;
use App\Enums\MembershipRole;
use App\Features\Troopers\Queries\GetTroopersByRoleQuery;
use App\Mail\Admin\Troopers\TrooperAwaitingApproval;
use App\Models\Trooper;
use App\Policies\TrooperPolicy;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

/**
 * Sends awaiting approval notifications to admins and moderators when a trooper registers.
 *
 * This job notifies all administrators and moderators with valid email addresses
 * when a new trooper completes registration. This allows admins to review and approve
 * pending trooper accounts in a timely manner.
 */
class SendTrooperRegisteredNotificationsJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     *
     * @param  Trooper  $trooper  The newly registered trooper awaiting approval.
     */
    public function __construct(private readonly Trooper $trooper)
    {
        //
    }

    /**
     * Execute the job.
     *
     * Retrieves all administrators and moderators and sends them notification emails
     * about the newly registered trooper awaiting approval. Only troopers with valid
     * email addresses receive notifications.
     *
     * @param  MagicBus  $bus  The message bus for dispatching queries
     */
    public function handle(MagicBus $bus): void
    {
        // This line is what triggers the verify your email, to be sent after
        // registration.
        event(new Registered($this->trooper));

        $admins_query = new GetTroopersByRoleQuery(MembershipRole::ADMINISTRATOR);

        $admins = $bus->send($admins_query);

        foreach ($admins as $admin)
        {
            if ($admin->emailAppearsValid() && $admin->wantsNotification('trooper_registrations', 'mail'))
            {
                Mail::to($admin->email)->queue(new TrooperAwaitingApproval);
            }
        }

        $moderators_query = new GetTroopersByRoleQuery(MembershipRole::MODERATOR);

        $moderators = $bus->send($moderators_query);

        $policy = new TrooperPolicy;

        foreach ($moderators as $moderator)
        {
            if ($moderator->emailAppearsValid()
                && $moderator->wantsNotification('trooper_registrations', 'mail')
                && $policy->moderate($moderator, $this->trooper))
            {
                Mail::to($moderator->email)->queue(new TrooperAwaitingApproval);
            }
        }
    }
}
