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
        config([
            'tracker.auth.require_xenforo' => false,
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-secret',
            'services.google.redirect' => 'https://example.com/google-callback',
            'services.xenforo.client_id' => null,
            'services.xenforo.client_secret' => null,
            'services.xenforo.redirect' => null,
        ]);

        $this->instance(TroopTracker::class, new TroopTracker);

        $this->assertTrue(TroopTrackerFacade::isGoogleOAuthEnabled());
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
