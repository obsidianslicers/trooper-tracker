<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\UpdateLastActiveMiddleware;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UpdateLastActiveMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    private function run_middleware(Request $request): void
    {
        (new UpdateLastActiveMiddleware())->handle($request, fn($req) => response('ok'));
    }

    public function test_passes_through_unauthenticated_request_without_error(): void
    {
        $request = Request::create('/');

        $response = (new UpdateLastActiveMiddleware())->handle(
            $request,
            fn($req) => response('ok')
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_sets_last_active_when_missing_for_authenticated_trooper(): void
    {
        $now = Carbon::parse('2026-06-25 10:00:00');
        Carbon::setTestNow($now);

        $trooper = Trooper::factory()->create([
            Trooper::LAST_ACTIVE_AT => null,
        ]);

        $this->actingAs($trooper);

        $this->run_middleware(Request::create('/'));

        $trooper->refresh();

        $this->assertSame($now->toDateTimeString(), $trooper->{Trooper::LAST_ACTIVE_AT}?->toDateTimeString());
    }

    public function test_does_not_update_last_active_within_three_minute_throttle_window(): void
    {
        $now = Carbon::parse('2026-06-25 10:00:00');
        Carbon::setTestNow($now);

        $existing_last_active_at = $now->copy()->subMinutes(2);

        $trooper = Trooper::factory()->create([
            Trooper::LAST_ACTIVE_AT => $existing_last_active_at,
        ]);

        $this->actingAs($trooper);

        $this->run_middleware(Request::create('/'));

        $trooper->refresh();

        $this->assertSame(
            $existing_last_active_at->toDateTimeString(),
            $trooper->{Trooper::LAST_ACTIVE_AT}?->toDateTimeString()
        );
    }

    public function test_updates_last_active_when_more_than_three_minutes_old(): void
    {
        $now = Carbon::parse('2026-06-25 10:00:00');
        Carbon::setTestNow($now);

        $trooper = Trooper::factory()->create([
            Trooper::LAST_ACTIVE_AT => $now->copy()->subMinutes(5),
        ]);

        $this->actingAs($trooper);

        $this->run_middleware(Request::create('/'));

        $trooper->refresh();

        $this->assertSame($now->toDateTimeString(), $trooper->{Trooper::LAST_ACTIVE_AT}?->toDateTimeString());
    }
}
