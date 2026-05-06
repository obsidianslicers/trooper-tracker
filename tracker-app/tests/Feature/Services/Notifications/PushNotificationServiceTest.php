<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Notifications;

use App\Models\MobileDevice;
use App\Models\PushNotification;
use App\Models\Trooper;
use App\Services\Notifications\PushNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\MessagingError;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use Tests\TestCase;

class PushNotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_notification_even_when_firebase_is_not_configured(): void
    {
        $trooper = Trooper::factory()->create();

        $service = new PushNotificationService(null);
        $service->sendToTrooper($trooper, 'Celebration VIII', 'Orlando Convention Center');

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'Celebration VIII',
            PushNotification::BODY       => 'Orlando Convention Center',
            PushNotification::URL        => '/events',
        ]);
    }

    public function test_records_notification_with_custom_url(): void
    {
        $trooper = Trooper::factory()->create();

        $service = new PushNotificationService(null);
        $service->sendToTrooper($trooper, 'Event Alert', 'Details inside', '/events/details/42');

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::URL        => '/events/details/42',
        ]);
    }

    public function test_skips_fcm_delivery_when_firebase_is_not_configured(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id]);

        // Should not throw even though a device is registered
        $service = new PushNotificationService(null);
        $service->sendToTrooper($trooper, 'Title', 'Body');

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_skips_multicast_when_trooper_has_no_registered_devices(): void
    {
        $trooper = Trooper::factory()->create();

        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->never())->method('sendMulticast');

        $service = new PushNotificationService($messaging);
        $service->sendToTrooper($trooper, 'Title', 'Body');

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_sends_multicast_when_trooper_has_registered_devices(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->count(2)->create([MobileDevice::TROOPER_ID => $trooper->id]);

        $report = MulticastSendReport::withItems([]);

        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->once())->method('sendMulticast')->willReturn($report);

        $service = new PushNotificationService($messaging);
        $service->sendToTrooper($trooper, 'Title', 'Body');

        $this->assertDatabaseHas('tt_push_notifications', [
            PushNotification::TROOPER_ID => $trooper->id,
        ]);
    }

    public function test_badge_count_in_apns_config_reflects_unread_notifications(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id]);

        PushNotification::create([
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'Older notification 1',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
        ]);
        PushNotification::create([
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'Older notification 2',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
        ]);

        $capturedMessage = null;

        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->once())
            ->method('sendMulticast')
            ->willReturnCallback(function ($message, $tokens) use (&$capturedMessage) {
                $capturedMessage = $message;

                return MulticastSendReport::withItems([]);
            });

        $service = new PushNotificationService($messaging);
        $service->sendToTrooper($trooper, 'New Notification', 'Come join us');

        // 3 total unread: 2 pre-existing + 1 just created
        $serialized = json_decode(json_encode($capturedMessage), true);
        $this->assertSame(3, $serialized['apns']['payload']['aps']['badge']);
    }

    public function test_removes_device_with_invalid_fcm_token_after_multicast(): void
    {
        $trooper = Trooper::factory()->create();
        $device = MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id]);
        $token = $device->fcm_token;

        $target = MessageTarget::with(MessageTarget::TOKEN, $token);
        $error  = new MessagingError('Registration token is not a valid FCM registration token');
        $report = MulticastSendReport::withItems([SendReport::failure($target, $error)]);

        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willReturn($report);

        $service = new PushNotificationService($messaging);
        $service->sendToTrooper($trooper, 'Title', 'Body');

        $this->assertDatabaseMissing('tt_mobile_devices', [MobileDevice::ID => $device->id]);
    }

    public function test_keeps_device_when_token_failure_is_transient(): void
    {
        $trooper = Trooper::factory()->create();
        $device = MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id]);
        $token = $device->fcm_token;

        $target = MessageTarget::with(MessageTarget::TOKEN, $token);
        $error  = new MessagingError('Quota exceeded for this application');
        $report = MulticastSendReport::withItems([SendReport::failure($target, $error)]);

        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willReturn($report);

        $service = new PushNotificationService($messaging);
        $service->sendToTrooper($trooper, 'Title', 'Body');

        $this->assertDatabaseHas('tt_mobile_devices', [MobileDevice::ID => $device->id]);
    }
}
