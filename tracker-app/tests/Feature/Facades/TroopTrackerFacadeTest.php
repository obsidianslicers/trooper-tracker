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
}
