<?php

declare(strict_types=1);

namespace App\Channels;

use App\Models\MobileDevice;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;

final class FcmChannel
{
    public function __construct(private readonly ?Messaging $messaging = null) {}

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

        $report = $this->messaging->sendMulticast($message, $tokens);

        foreach ($report->failures()->getItems() as $failure)
        {
            if ($failure->messageTargetWasInvalid())
            {
                MobileDevice::where(MobileDevice::FCM_TOKEN, $failure->target()->value())->delete();
            }
        }
    }
}
