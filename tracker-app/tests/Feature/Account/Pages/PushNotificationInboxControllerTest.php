<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Pages;

use App\Enums\EventTrooperStatus;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class PushNotificationInboxControllerTest extends TestCase
{
    use BuildsTroopsToConfirm;
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

    public function test_troops_to_confirm_come_first_with_both_answers(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troops = collect(range(1, 6))->map(fn (int $days_ago) => $this->troopToConfirm(
            $trooper,
            ended_at: now()->subDays($days_ago),
            event_name: "Troop {$days_ago} days back",
        ));
        TrooperNotification::factory()->forTrooper($trooper)->withData(['title' => 'Holiday Parade is open'])->create();

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        //  every one of them, not the bell's five
        $this->assertCount(6, $response->viewData('troops_to_confirm'));
        $response->assertSeeInOrder(['Did you make it?', 'Troop 6 days back', 'Troop 1 days back', 'Holiday Parade is open']);
        $response->assertSee(route('events.confirm-attendance', $troops->first()), false);
        $response->assertSee('value="'.EventTrooperStatus::UNABLE_TO_ATTEND->value.'"', false);
        $response->assertDontSee('No notifications yet.');
    }

    public function test_attendance_reminders_are_left_out_of_the_list(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperNotification::factory()->forTrooper($trooper)->create([
            TrooperNotification::TYPE => EventShiftCompletedNotification::class,
            TrooperNotification::DATA => ['title' => 'Event Shift Completed for Spring Fling'],
        ]);

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        $this->assertCount(0, $response->viewData('notifications'));
        $response->assertSee('No notifications yet.');
    }
}
