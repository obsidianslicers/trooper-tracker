<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Notifications;

use App\Channels\FcmChannel;
use App\Models\MobileDevice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Exception\Messaging\MessagingError;
use Kreait\Firebase\Exception\Messaging\ServerUnavailable;
use Kreait\Firebase\Exception\MessagingException;
use Kreait\Firebase\Messaging\MessageTarget;
use Kreait\Firebase\Messaging\MulticastSendReport;
use Kreait\Firebase\Messaging\SendReport;
use RuntimeException;
use Tests\TestCase;

class FcmChannelTest extends TestCase
{
    use RefreshDatabase;

    private function makeNotification(): Notification
    {
        return new class extends Notification {
            public function toFcm(object $notifiable): array
            {
                return ['title' => 'Test', 'body' => 'Body', 'url' => '/events'];
            }
        };
    }

    private function makeMessagingError(): MessagingException
    {
        return new class('invalid token') extends RuntimeException implements MessagingException {
            public function errors(): array
            {
                return [];
            }
        };
    }

    private function makeSuccessReport(array $tokens): MulticastSendReport
    {
        $items = array_map(
            fn ($token) => SendReport::success(
                MessageTarget::with(MessageTarget::TOKEN, $token),
                ['name' => 'projects/test/messages/fake']
            ),
            $tokens
        );

        return MulticastSendReport::withItems($items);
    }

    private function makeFailureReport(string $token): MulticastSendReport
    {
        $item = SendReport::failure(
            MessageTarget::with(MessageTarget::TOKEN, $token),
            $this->makeMessagingError()
        );

        return MulticastSendReport::withItems([$item]);
    }

    public function test_returns_early_when_messaging_is_null(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id]);

        $channel = new FcmChannel(null);
        $channel->send($trooper, $this->makeNotification());

        $this->assertTrue(true);
    }

    public function test_returns_early_when_no_devices_registered(): void
    {
        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->never())->method('sendMulticast');

        $trooper = Trooper::factory()->create();

        $channel = new FcmChannel($messaging);
        $channel->send($trooper, $this->makeNotification());
    }

    public function test_sends_to_all_trooper_tokens(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'token-a']);
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'token-b']);

        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->once())
            ->method('sendMulticast')
            ->with($this->anything(), $this->containsEqual('token-a'))
            ->willReturn($this->makeSuccessReport(['token-a', 'token-b']));

        $channel = new FcmChannel($messaging);
        $channel->send($trooper, $this->makeNotification());
    }

    public function test_deletes_invalid_tokens_on_failure(): void
    {
        $trooper = Trooper::factory()->create();
        $device = MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'bad-token']);

        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willReturn($this->makeFailureReport('bad-token'));

        $channel = new FcmChannel($messaging);
        $channel->send($trooper, $this->makeNotification());

        $this->assertDatabaseMissing('tt_mobile_devices', [MobileDevice::ID => $device->id]);
    }

    public function test_retries_transient_server_errors_then_succeeds(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'token-a']);

        $calls = 0;
        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->exactly(2))
            ->method('sendMulticast')
            ->willReturnCallback(function () use (&$calls) {
                if (++$calls === 1)
                {
                    throw new ServerUnavailable('temporarily unavailable');
                }

                return $this->makeSuccessReport(['token-a']);
            });

        (new FcmChannel($messaging))->send($trooper, $this->makeNotification());

        $this->assertSame(2, $calls);
    }

    public function test_swallows_persistent_transient_errors_without_throwing(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'token-a']);

        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->exactly(3))
            ->method('sendMulticast')
            ->willThrowException(new ServerUnavailable('down'));

        (new FcmChannel($messaging))->send($trooper, $this->makeNotification());

        $this->assertTrue(true);
    }

    public function test_swallows_permanent_errors_without_retrying(): void
    {
        $trooper = Trooper::factory()->create();
        MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'token-a']);

        $messaging = $this->createMock(Messaging::class);
        $messaging->expects($this->once())
            ->method('sendMulticast')
            ->willThrowException(new MessagingError('invalid request'));

        (new FcmChannel($messaging))->send($trooper, $this->makeNotification());

        $this->assertTrue(true);
    }

    public function test_does_not_delete_valid_tokens_on_success(): void
    {
        $trooper = Trooper::factory()->create();
        $device = MobileDevice::factory()->create([MobileDevice::TROOPER_ID => $trooper->id, MobileDevice::FCM_TOKEN => 'good-token']);

        $messaging = $this->createMock(Messaging::class);
        $messaging->method('sendMulticast')->willReturn($this->makeSuccessReport(['good-token']));

        $channel = new FcmChannel($messaging);
        $channel->send($trooper, $this->makeNotification());

        $this->assertDatabaseHas('tt_mobile_devices', [MobileDevice::ID => $device->id]);
    }
}
