<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Account\Queries;

use App\Messages\Account\Queries\GetTrooperNotifications;
use App\Models\Trooper;
use App\Models\TrooperNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetTrooperNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_call_returns_latest_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        TrooperNotification::factory()
            ->forTrooper($trooper)
            ->create(['created_at' => now()->subMinutes(2)]);

        TrooperNotification::factory()
            ->forTrooper($trooper)
            ->create(['created_at' => now()->subMinutes(1)]);

        TrooperNotification::factory()
            ->forTrooper($trooper)
            ->create(['created_at' => now()]);

        $subject = new GetTrooperNotifications($trooper);
        $result = $subject->handle();

        $this->assertCount(3, $result);
        $this->assertTrue($result->first()->created_at >= $result->last()->created_at);
    }

    public function test_call_returns_empty_collection_when_no_notifications(): void
    {
        $trooper = Trooper::factory()->asActive()->create();

        $subject = new GetTrooperNotifications($trooper);
        $result = $subject->handle();

        $this->assertCount(0, $result);
    }

    public function test_call_only_returns_notifications_for_that_trooper(): void
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

        $subject = new GetTrooperNotifications($trooper1);
        $result = $subject->handle();

        $this->assertCount(2, $result);
    }
}
