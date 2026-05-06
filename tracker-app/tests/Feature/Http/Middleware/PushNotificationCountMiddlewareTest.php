<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\PushNotificationCountMiddleware;
use App\Models\PushNotification;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class PushNotificationCountMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function runMiddleware(Request $request): void
    {
        (new PushNotificationCountMiddleware())->handle($request, fn($req) => response('ok'));
    }

    public function test_shares_zero_for_unauthenticated_request(): void
    {
        $this->runMiddleware(Request::create('/'));

        $this->assertSame(0, View::shared('pushNotificationUnreadCount'));
    }

    public function test_shares_unread_count_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        PushNotification::create([
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'Event 1',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
        ]);
        PushNotification::create([
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'Event 2',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
        ]);

        $this->runMiddleware(Request::create('/'));

        $this->assertSame(2, View::shared('pushNotificationUnreadCount'));
    }

    public function test_shares_zero_when_all_notifications_are_read(): void
    {
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        PushNotification::create([
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'Old Event',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
            PushNotification::READ_AT    => now(),
        ]);

        $this->runMiddleware(Request::create('/'));

        $this->assertSame(0, View::shared('pushNotificationUnreadCount'));
    }

    public function test_only_counts_notifications_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $other   = Trooper::factory()->create();
        $this->actingAs($trooper);

        PushNotification::create([
            PushNotification::TROOPER_ID => $other->id,
            PushNotification::TITLE      => 'Other trooper notification',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
        ]);
        PushNotification::create([
            PushNotification::TROOPER_ID => $trooper->id,
            PushNotification::TITLE      => 'My notification',
            PushNotification::BODY       => 'body',
            PushNotification::URL        => '/events',
        ]);

        $this->runMiddleware(Request::create('/'));

        $this->assertSame(1, View::shared('pushNotificationUnreadCount'));
    }
}
