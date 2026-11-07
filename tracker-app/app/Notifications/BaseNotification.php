<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\FcmChannel;
use App\Enums\AdministrativeNotifications;
use App\Enums\TrooperNotifications;
use App\Mail\OutgoingMailBudget;
use App\Mail\ThrottleOutgoingMail;
use App\Models\Trooper;
use DateTimeInterface;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueueAfterCommit;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

/**
 * All notifications queue per recipient. Delivery therefore never runs inline in
 * the fan-out loop that dispatched it, so a failed send retries just that one
 * recipient instead of the enclosing job re-notifying everyone.
 *
 * ShouldQueueAfterCommit: notifications are frequently dispatched from inside a
 * database transaction (ShouldBeTransactional handlers), so the queued job must
 * wait for commit or it can run before the rows it depends on exist.
 */
class BaseNotification extends Notification implements ShouldQueueAfterCommit
{
    use Queueable;
    use SerializesModels;

    /**
     * Retry each recipient's delivery a few times on transient failure; the
     * queue copies this onto the per-recipient SendQueuedNotifications job.
     */
    public int $tries = 3;

    /**
     * Still caps real failures once retryUntil() takes over from $tries.
     */
    public int $maxExceptions = 3;

    protected AdministrativeNotifications|TrooperNotifications|string|null $notification_category = null;

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [30, 60];
    }

    public function retryUntil(): ?DateTimeInterface
    {
        return app(OutgoingMailBudget::class)->retryUntil();
    }

    /**
     * Only email counts against the host's sending cap.
     *
     * @return list<object>
     */
    public function middleware(object $notifiable, string $channel): array
    {
        return $channel === 'mail' ? [new ThrottleOutgoingMail] : [];
    }

    public function via(Trooper $notifiable): array
    {
        if ($this->notification_category === null)
        {
            throw new Exception('Notification category ['.get_class($this).'] is not set.');
        }

        if (is_string($this->notification_category))
        {
            $category = $this->notification_category;
        }
        else
        {
            $category = $this->notification_category->value;
        }

        $channels = [];

        if ($notifiable->wantsNotification($category, 'database'))
        {
            $channels[] = 'database';
        }

        if ($notifiable->push_notifications_enabled && $notifiable->wantsNotification($category, 'fcm'))
        {
            $channels[] = FcmChannel::class;
        }

        if ($notifiable->emailAppearsValid() && $notifiable->wantsNotification($category, 'mail'))
        {
            $channels[] = 'mail';
        }

        return $channels;
    }
}
