<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EventNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_notification(): void
    {
        $subject = EventNotification::factory()->create();

        $this->assertInstanceOf(EventNotification::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}