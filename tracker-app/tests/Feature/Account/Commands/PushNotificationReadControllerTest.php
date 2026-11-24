<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Commands;

use App\Messages\Troopers\Commands\Notifications\MarkPushNotificationAsRead;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\FakesMessages;
use Tests\TestCase;

class PushNotificationReadControllerTest extends TestCase
{
    use FakesMessages;
    use RefreshDatabase;

    public function test_push_notification_read_controller_marks_as_read(): void
    {
        $messages = $this->fakeMessages([
            MarkPushNotificationAsRead::class => null,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData(['url' => '/dashboard'])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('account.push-notifications.read', $notification)
        );

        $response->assertRedirect();
        $messages->assertCalled(MarkPushNotificationAsRead::class);
    }

    public function test_push_notification_read_controller_redirects_to_notification_url(): void
    {
        $messages = $this->fakeMessages([
            MarkPushNotificationAsRead::class => null,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData(['url' => '/events/upcoming'])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('account.push-notifications.read', $notification)
        );

        $response->assertRedirect('/events/upcoming');
        $messages->assertCalled(MarkPushNotificationAsRead::class);
    }

    public function test_push_notification_read_controller_sends_the_svelte_page_to_the_url(): void
    {
        $this->fakeMessages([MarkPushNotificationAsRead::class => null]);
        $trooper = Trooper::factory()->asActive()->create();
        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData(['url' => '/events/upcoming'])
            ->create();

        $response = $this->actingAs($trooper)
            ->withHeader('X-Inertia', 'true')
            ->post(route('account.push-notifications.read', $notification));

        //  a full visit, so the notification can lead to a Blade page or another site
        $response->assertStatus(409);
        $response->assertHeader('X-Inertia-Location', '/events/upcoming');
    }

    public function test_push_notification_read_controller_denies_access_to_other_trooper_notification(): void
    {
        $messages = $this->fakeMessages([
            MarkPushNotificationAsRead::class => null,
        ]);

        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        $notification = TrooperNotification::factory()
            ->forTrooper($trooper1)
            ->withData(['url' => '/dashboard'])
            ->create();

        $response = $this->actingAs($trooper2)->post(
            route('account.push-notifications.read', $notification)
        );

        $response->assertStatus(403);
    }

    public function test_push_notification_read_controller_defaults_to_events_on_missing_url(): void
    {
        $messages = $this->fakeMessages([
            MarkPushNotificationAsRead::class => null,
        ]);

        $trooper = Trooper::factory()->asActive()->create();
        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData([])
            ->create();

        $response = $this->actingAs($trooper)->post(
            route('account.push-notifications.read', $notification)
        );

        $response->assertRedirect('/events');
        $messages->assertCalled(MarkPushNotificationAsRead::class);
    }
}
