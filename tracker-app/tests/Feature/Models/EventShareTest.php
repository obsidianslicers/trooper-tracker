<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EventShare;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventShareTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_share(): void
    {
        $subject = EventShare::factory()->create();

        $this->assertInstanceOf(EventShare::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }

    public function test_get_route_key_name_returns_share_token(): void
    {
        $subject = new EventShare();

        $result = $subject->getRouteKeyName();

        $this->assertSame('share_token', $result);
    }

    public function test_get_is_viewable_attribute_returns_true_when_not_revoked_and_not_expired(): void
    {
        $subject = EventShare::factory()
            ->state([
                EventShare::IS_REVOKED => false,
                EventShare::EXPIRES_AT => Carbon::now()->addDays(5),
            ])
            ->create();

        $this->assertTrue($subject->is_viewable);
    }

    public function test_get_is_viewable_attribute_returns_false_when_revoked(): void
    {
        $subject = EventShare::factory()
            ->state([
                EventShare::IS_REVOKED => true,
                EventShare::EXPIRES_AT => Carbon::now()->addDays(5),
            ])
            ->create();

        $this->assertFalse($subject->is_viewable);
    }

    public function test_get_is_viewable_attribute_returns_false_when_expired(): void
    {
        $subject = EventShare::factory()
            ->state([
                EventShare::IS_REVOKED => false,
                EventShare::EXPIRES_AT => Carbon::now()->subDays(1),
            ])
            ->create();

        $this->assertFalse($subject->is_viewable);
    }
}