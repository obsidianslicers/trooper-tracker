<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Commands;

use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PushNotificationClearControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_push_notification_clear_controller_deletes_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->count(5)
            ->forTrooper($trooper)
            ->create();

        $response = $this->actingAs($trooper)
            ->from(route('faq'))
            ->delete(route('account.push-notifications.clear'));

        $response->assertRedirect(route('faq'));

        $remaining = TrooperNotification::where('notifiable_id', $trooper->id)
            ->where('notifiable_type', Trooper::class)
            ->count();

        $this->assertSame(0, $remaining);
    }

    public function test_push_notification_clear_controller_does_not_affect_other_troopers(): void
    {
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->count(2)
            ->forTrooper($trooper1)
            ->create();

        TrooperNotification::factory()
            ->count(3)
            ->forTrooper($trooper2)
            ->create();

        $this->actingAs($trooper1)->delete(route('account.push-notifications.clear'));

        $trooper1_count = TrooperNotification::where('notifiable_id', $trooper1->id)
            ->where('notifiable_type', Trooper::class)
            ->count();

        $trooper2_count = TrooperNotification::where('notifiable_id', $trooper2->id)
            ->where('notifiable_type', Trooper::class)
            ->count();

        $this->assertSame(0, $trooper1_count);
        $this->assertSame(3, $trooper2_count);
    }
}
