<?php

declare(strict_types=1);

namespace Tests\Feature\View\Composers;

use App\Models\Trooper;
use App\Models\TrooperNotification;
use App\View\Composers\NavbarNotificationsComposer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarNotificationsComposerTest extends TestCase
{
    use RefreshDatabase;

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
}
