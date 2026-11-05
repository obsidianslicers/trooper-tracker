<?php

declare(strict_types=1);

namespace Tests\Feature\Facades;

use App\Facades\TroopTracker;
use App\Facades\TroopTrackerFacade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TroopTrackerFacadeTest extends TestCase
{
    use RefreshDatabase;

    public function test_facade_delegates_to_bound_troop_tracker_instance(): void
    {
        $this->instance(TroopTracker::class, new TroopTracker);

        $this->assertTrue(TroopTrackerFacade::isEmailPasswordAuthEnabled());
    }

    public function test_facade_returns_false_when_underlying_service_returns_false(): void
    {
        config([
            'services.xenforo.base_url' => 'https://forums.example.com',
            'services.xenforo.api_key' => null,
        ]);

        $this->instance(TroopTracker::class, new TroopTracker);

        $this->assertFalse(TroopTrackerFacade::isXenforoIntegrationConfigured());
    }
}
