<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account\Commands;

use App\Enums\NotificationChannels;
use App\Enums\TrooperNotifications;
use App\Models\Trooper;
use App\Plugins\PluginNotifications;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class UpdateNotificationPreferenceControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_core_notification_preference(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->post(route('account.update-notification-preference'), [
            'notification' => TrooperNotifications::EVENT_CREATED->value,
            'channel' => NotificationChannels::MAIL->value,
            'enabled' => false,
        ]);

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page->component('account/Index'));
        $this->assertFalse($trooper->refresh()->wantsNotification(
            TrooperNotifications::EVENT_CREATED->value,
            NotificationChannels::MAIL->value,
        ));
    }

    public function test_invoke_updates_plugin_notification_preference(): void
    {
        app(PluginNotifications::class)->add('plugin_forum_post', 'Forum replies');
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->post(route('account.update-notification-preference'), [
            'notification' => 'plugin_forum_post',
            'channel' => NotificationChannels::MAIL->value,
            'enabled' => false,
        ]);

        $response->assertOk();
        $this->assertSame(
            ['plugin_forum_post' => [NotificationChannels::MAIL->value => false]],
            $trooper->refresh()->{Trooper::NOTIFICATION_PREFERENCES},
        );
    }
}
