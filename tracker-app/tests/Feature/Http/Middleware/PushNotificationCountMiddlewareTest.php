<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\PushNotificationCountMiddleware;
use App\Models\Trooper;
use App\Models\TrooperNotification;
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

        TrooperNotification::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'TestNotification',
            'notifiable_type' => Trooper::class,
            'notifiable_id'   => $trooper->id,
            'data'            => json_encode(['title' => 'Event 1', 'body' => 'body', 'url' => '/events']),
            'read_at'         => null,
        ]);
        TrooperNotification::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'TestNotification',
            'notifiable_type' => Trooper::class,
            'notifiable_id'   => $trooper->id,
            'data'            => json_encode(['title' => 'Event 2', 'body' => 'body', 'url' => '/events']),
            'read_at'         => null,
        ]);

        $this->runMiddleware(Request::create('/'));

        $this->assertSame(2, View::shared('pushNotificationUnreadCount'));
    }

    public function test_shares_zero_when_all_notifications_are_read(): void
    {
        $trooper = Trooper::factory()->create();
        $this->actingAs($trooper);

        TrooperNotification::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'TestNotification',
            'notifiable_type' => Trooper::class,
            'notifiable_id'   => $trooper->id,
            'data'            => json_encode(['title' => 'Old Event', 'body' => 'body', 'url' => '/events']),
            'read_at'         => now(),
        ]);

        $this->runMiddleware(Request::create('/'));

        $this->assertSame(0, View::shared('pushNotificationUnreadCount'));
    }

    public function test_only_counts_notifications_for_authenticated_trooper(): void
    {
        $trooper = Trooper::factory()->create();
        $other   = Trooper::factory()->create();
        $this->actingAs($trooper);

        TrooperNotification::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'TestNotification',
            'notifiable_type' => Trooper::class,
            'notifiable_id'   => $other->id,
            'data'            => json_encode(['title' => 'Other', 'body' => 'body', 'url' => '/events']),
            'read_at'         => null,
        ]);
        TrooperNotification::create([
            'id'              => \Illuminate\Support\Str::uuid(),
            'type'            => 'TestNotification',
            'notifiable_type' => Trooper::class,
            'notifiable_id'   => $trooper->id,
            'data'            => json_encode(['title' => 'Mine', 'body' => 'body', 'url' => '/events']),
            'read_at'         => null,
        ]);

        $this->runMiddleware(Request::create('/'));

        $this->assertSame(1, View::shared('pushNotificationUnreadCount'));
    }
}
