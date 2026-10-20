<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\MobileDevice;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\ApiConnectionFailed;
use Kreait\Firebase\Exception\Messaging\QuotaExceeded;
use Kreait\Firebase\Exception\Messaging\ServerError;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

final class FcmChannel
{
    /**
     * Extra in-process attempts on transient Firebase backend errors.
     */
    private const TRANSIENT_RETRIES = 2;

    private const RETRY_SLEEP_MS = 200;

    public function __construct(private readonly ?Messaging $messaging = null) {}

    /**
     * Sends a Firebase notification to all registered device tokens.
     *
     * Push delivery is best-effort: transient backend errors are retried a few
     * times, and any failure is logged and swallowed so it never fails the
     * surrounding (non-idempotent) notification job.
     *
     * @param  object  $notifiable  Trooper-like notifiable entity with id and unread notifications.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if ($this->messaging === null || !method_exists($notification, 'toFcm'))
        {
            return;
        }

        $tokens = MobileDevice::where(MobileDevice::TROOPER_ID, $notifiable->id)
            ->pluck(MobileDevice::FCM_TOKEN)
            ->toArray();

        if (empty($tokens))
        {
            return;
        }

        $report = $this->deliver($notifiable, $this->buildMessage($notifiable, $notification), $tokens);

        if ($report === null)
        {
            return;
        }

        $this->pruneInvalidTokens($report);
    }

    private function buildMessage(object $notifiable, Notification $notification): CloudMessage
    {
        $data = $notification->toFcm($notifiable);
        $unread_count = $notifiable->unreadNotifications()->count();

        return CloudMessage::new()
            ->withNotification(FcmNotification::create($data['title'], $data['body']))
            ->withData(['url' => $data['url']])
            ->withApnsConfig([
                'payload' => ['aps' => ['badge' => $unread_count]],
            ]);
    }

    /**
     * @param  array<int, string>  $tokens
     */
    private function deliver(object $notifiable, CloudMessage $message, array $tokens): ?MulticastSendReport
    {
        $attempt = 0;

        while (true)
        {
            try
            {
                return $this->messaging->sendMulticast($message, $tokens);
            }
            catch (ServerUnavailable|ServerError|QuotaExceeded|ApiConnectionFailed $exception)
            {
                if (++$attempt > self::TRANSIENT_RETRIES)
                {
                    Log::warning('FCM unavailable, giving up', [
                        'trooper_id' => $notifiable->id,
                        'exception' => $exception->getMessage(),
                    ]);

                    return null;
                }

                usleep(self::RETRY_SLEEP_MS * 1000 * $attempt);
            }
            catch (Throwable $exception)
            {
                Log::warning('FCM send failed', [
                    'trooper_id' => $notifiable->id,
                    'exception' => $exception->getMessage(),
                ]);

                return null;
            }
        }
    }

    private function pruneInvalidTokens(MulticastSendReport $report): void
    {
        foreach ($report->failures()->getItems() as $failure)
        {
            if ($failure->messageTargetWasInvalid())
            {
                MobileDevice::where(MobileDevice::FCM_TOKEN, $failure->target()->value())->delete();
            }
        }
    }
}
