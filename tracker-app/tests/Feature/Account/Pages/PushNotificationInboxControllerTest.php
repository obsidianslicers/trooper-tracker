<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Pages;

use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushNotificationInboxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_notification_inbox_controller_displays_page(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->count(3)
            ->forTrooper($trooper)
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        $response->assertOk();
        $response->assertViewIs('pages.account.push-notifications');
    }

    public function test_push_notification_inbox_controller_passes_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->count(2)
            ->forTrooper($trooper)
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        $response->assertViewHas('notifications');
        $this->assertCount(2, $response->viewData('notifications'));
    }
}
