<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\NoticeTrooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeTrooperTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_notice_trooper(): void
    {
        $subject = NoticeTrooper::factory()->create();

        $this->assertInstanceOf(NoticeTrooper::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}