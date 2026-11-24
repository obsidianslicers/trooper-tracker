<?php

declare(strict_types=1);

namespace Tests\Feature\Account\Pages;

use App\Models\Trooper;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
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
        $response->assertInertia(fn (Assert $page) => $page
            ->component('account/PushNotifications')
            ->has('notifications', 3)
            ->has('troops_to_confirm', 0));
    }

    public function test_push_notification_inbox_controller_sends_only_what_the_row_shows(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData(['title' => 'Holiday Parade is open', 'body' => 'Sign up now', 'url' => '/events'])
            ->create();

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        $response->assertInertia(fn (Assert $page) => $page
            ->has('notifications.0', fn (Assert $row) => $row
                ->where('id', (string) $notification->id)
                ->where('title', 'Holiday Parade is open')
                ->where('body', 'Sign up now')
                ->where('is_unread', true)
                ->has('sent_ago')));
    }

    public function test_troops_to_confirm_come_first_with_both_answers(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troops = collect(range(1, 6))->map(fn (int $days_ago) => $this->troopToConfirm(
            $trooper,
            ended_at: now()->subDays($days_ago),
            event_name: "Troop {$days_ago} days back",
        ));
        $first = $troops->first();

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        //  every one of them, not the bell's five, oldest first
        $response->assertInertia(fn (Assert $page) => $page
            ->has('troops_to_confirm', 6)
            ->where('troops_to_confirm.0.event_name', 'Troop 6 days back')
            ->where('troops_to_confirm.5.event_name', 'Troop 1 days back')
            ->where('troops_to_confirm.5.id', $first->id)
            ->where(
                'troops_to_confirm.5.url',
                route('events.display', ['event' => $first->event_shift->event_id])
                    .'#shift-'.$first->event_shift_id,
            ));
    }

    public function test_attendance_reminders_are_left_out_of_the_list(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperNotification::factory()->forTrooper($trooper)->create([
            TrooperNotification::TYPE => EventShiftCompletedNotification::class,
            TrooperNotification::DATA => ['title' => 'Event Shift Completed for Spring Fling'],
        ]);

        $response = $this->actingAs($trooper)->get(route('account.push-notifications'));

        $response->assertInertia(fn (Assert $page) => $page->has('notifications', 0));
    }
}
