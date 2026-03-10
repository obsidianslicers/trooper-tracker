<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\EventUploadTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventUploadTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_event_upload_trooper(): void
    {
        $subject = EventUploadTrooper::factory()->create();

        $this->assertInstanceOf(EventUploadTrooper::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}