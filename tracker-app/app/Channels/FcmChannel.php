<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\MobileDevice;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Throwable;

final class FcmChannel
{
    public function __construct(private readonly ?Messaging $messaging = null) {}

    /**
     * Sends a Firebase notification to all registered device tokens.
     *
     * @param  object  $notifiable  Trooper-like notifiable entity with id and unread notifications.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        if ($this->messaging === null)
        {
            return;
        }

        if (!method_exists($notification, 'toFcm'))
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

        $data = $notification->toFcm($notifiable);

        $unreadCount = $notifiable->unreadNotifications()->count();

        $message = CloudMessage::new()
            ->withNotification(FcmNotification::create($data['title'], $data['body']))
            ->withData(['url' => $data['url']])
            ->withApnsConfig([
                'payload' => ['aps' => ['badge' => $unreadCount]],
            ]);

        try
        {
            $report = $this->messaging->sendMulticast($message, $tokens);
        }
        catch (Throwable $exception)
        {
            Log::warning('FCM sendMulticast failed', [
                'trooper_id' => $notifiable->id,
                'exception' => $exception->getMessage(),
            ]);

            throw $exception;
        }

        foreach ($report->failures()->getItems() as $failure)
        {
            if ($failure->messageTargetWasInvalid())
            {
                MobileDevice::where(MobileDevice::FCM_TOKEN, $failure->target()->value())->delete();
            }
        }
    }
}
