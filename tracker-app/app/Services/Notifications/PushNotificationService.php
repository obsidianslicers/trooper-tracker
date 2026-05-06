<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\MobileDevice;
use App\Models\Trooper;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;

final class PushNotificationService
{
    public function __construct(private readonly Messaging $messaging) {}

    public function sendToTrooper(Trooper $trooper, string $title, string $body, string $url = '/events'): void
    {
        $tokens = MobileDevice::where(MobileDevice::TROOPER_ID, $trooper->id)
            ->pluck(MobileDevice::FCM_TOKEN)
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        $message = CloudMessage::new()
            ->withNotification(Notification::create($title, $body))
            ->withData(['url' => $url]);
        $report = $this->messaging->sendMulticast($message, $tokens);

        foreach ($report->failures()->getItems() as $failure) {
            if ($failure->messageTargetWasInvalid()) {
                MobileDevice::where(MobileDevice::FCM_TOKEN, $failure->target()->value())->delete();
            }
        }
    }
}
