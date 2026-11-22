<?php

declare(strict_types=1);

namespace Tests\Feature\View\Composers;

use App\Enums\EventTrooperStatus;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use App\Notifications\Events\EventShiftCompletedNotification;
use App\View\Composers\NavbarNotificationsComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\Concerns\BuildsTroopsToConfirm;
use Tests\TestCase;

class NavbarNotificationsComposerTest extends TestCase
{
    use BuildsTroopsToConfirm;
    use RefreshDatabase;

    /**
     * @return array<string, mixed> What the composer gives the bell.
     */
    private function bellFor(?Trooper $trooper): array
    {
        if ($trooper)
        {
            $this->actingAs($trooper);
        }

        $view = View::make('partials.inc.navbar-notifications');
        (new NavbarNotificationsComposer)->compose($view);

        return $view->getData();
    }

    public function test_the_bell_lists_the_troopers_notifications_on_any_page(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $notification = TrooperNotification::factory()
            ->forTrooper($trooper)
            ->withData(['title' => 'Holiday Parade is open', 'body' => 'Sign up now', 'url' => '/events'])
            ->asUnread()
            ->create();

        $response = $this->actingAs($trooper)->get(route('faq'));

        $response->assertOk();
        $response->assertSee('Holiday Parade is open');
        $response->assertSee('Sign up now');
        $response->assertSee(route('account.push-notifications.read', $notification), false);
        $response->assertSee(route('account.push-notifications.clear'), false);
        $response->assertSee(route('account.push-notifications'), false);
    }

    public function test_the_bell_previews_only_the_most_recent_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        foreach (range(1, NavbarNotificationsComposer::PREVIEW_COUNT + 1) as $minutes_ago)
        {
            TrooperNotification::factory()
                ->forTrooper($trooper)
                ->withData(['title' => "Notice from {$minutes_ago} minutes back"])
                ->create(['created_at' => now()->subMinutes($minutes_ago)]);
        }

        $response = $this->actingAs($trooper)->get(route('faq'));

        $response->assertSee('Notice from 1 minutes back');
        $response->assertSee('Notice from '.NavbarNotificationsComposer::PREVIEW_COUNT.' minutes back');
        $response->assertDontSee('Notice from '.(NavbarNotificationsComposer::PREVIEW_COUNT + 1).' minutes back');
    }

    public function test_the_bell_says_when_there_are_no_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $response = $this->actingAs($trooper)->get(route('faq'));

        $response->assertOk();
        $response->assertSee('No notifications yet.');
        $response->assertDontSee('Clear all');
    }

    public function test_the_bell_shows_only_the_troopers_own_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $other = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->forTrooper($other)
            ->withData(['title' => 'Someone else entirely'])
            ->create();

        $response = $this->actingAs($trooper)->get(route('faq'));

        $response->assertDontSee('Someone else entirely');
    }

    public function test_the_bell_pins_troops_to_confirm_with_both_answers(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $troop = $this->troopToConfirm($trooper, event_name: 'Spring Fling Parade');

        $response = $this->actingAs($trooper)->get(route('faq'));

        $response->assertSee('Did you make it?');
        $response->assertSee('Spring Fling Parade');
        $response->assertSee(route('events.confirm-attendance', $troop), false);
        $response->assertSee('value="'.EventTrooperStatus::ATTENDED->value.'"', false);
        $response->assertSee('value="'.EventTrooperStatus::UNABLE_TO_ATTEND->value.'"', false);
        $response->assertDontSee('No notifications yet.');
    }

    public function test_the_bell_pins_a_few_troops_and_links_to_the_rest(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        foreach (range(1, NavbarNotificationsComposer::TROOPS_TO_CONFIRM_PREVIEW_COUNT + 1) as $days_ago)
        {
            $this->troopToConfirm($trooper, ended_at: now()->subDays($days_ago));
        }

        $bell = $this->bellFor($trooper);

        $this->assertCount(NavbarNotificationsComposer::TROOPS_TO_CONFIRM_PREVIEW_COUNT, $bell['navbar_troops_to_confirm']);
        $this->assertSame(NavbarNotificationsComposer::TROOPS_TO_CONFIRM_PREVIEW_COUNT + 1, $bell['navbar_troops_to_confirm_count']);

        $this->actingAs($trooper)->get(route('faq'))
            ->assertSee(route('events.troops-to-confirm'), false);
    }

    public function test_the_bell_leaves_attendance_reminders_out_of_its_list(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        TrooperNotification::factory()->forTrooper($trooper)->asUnread()->create([
            TrooperNotification::TYPE => EventShiftCompletedNotification::class,
            TrooperNotification::DATA => ['title' => 'Event Shift Completed for Spring Fling'],
        ]);
        TrooperNotification::factory()->forTrooper($trooper)->asUnread()->withData(['title' => 'Holiday Parade is open'])->create();

        $bell = $this->bellFor($trooper);

        $this->assertSame(['Holiday Parade is open'], $bell['navbar_notifications']->pluck('title')->all());
    }

    public function test_the_badge_counts_unread_notifications_and_troops_to_confirm(): void
    {
        $trooper = Trooper::factory()->asActive()->create();
        $this->troopToConfirm($trooper);
        TrooperNotification::factory()->forTrooper($trooper)->asUnread()->create();
        TrooperNotification::factory()->forTrooper($trooper)->asRead()->create();
        //  the troop's reminder: the pinned troop already counts it
        TrooperNotification::factory()->forTrooper($trooper)->asUnread()->create([
            TrooperNotification::TYPE => EventShiftCompletedNotification::class,
        ]);
        TrooperNotification::factory()->forTrooper(Trooper::factory()->create())->asUnread()->create();

        $this->assertSame(2, $this->bellFor($trooper)['navbar_badge_count']);
    }

    public function test_the_bell_is_empty_for_a_guest(): void
    {
        $bell = $this->bellFor(null);

        $this->assertSame(0, $bell['navbar_badge_count']);
        $this->assertCount(0, $bell['navbar_troops_to_confirm']);
    }
}
