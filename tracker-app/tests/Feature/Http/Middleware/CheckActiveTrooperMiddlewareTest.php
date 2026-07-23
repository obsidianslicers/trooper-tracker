<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckActiveTrooperMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_through_active_trooper(): void
    {
        $trooper = Trooper::factory()
            ->asActive()
            ->withVerifiedEmail()
            ->create();

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertOk();
    }

    public function test_rejects_non_active_trooper(): void
    {
        $trooper = Trooper::factory()
            ->create([
                Trooper::MEMBERSHIP_STATUS => MembershipStatus::PENDING,
                Trooper::EMAIL_VERIFIED_AT => now(),
            ]);

        $response = $this->actingAs($trooper)->get(route('events.list'));

        $response->assertUnauthorized();
    }
}