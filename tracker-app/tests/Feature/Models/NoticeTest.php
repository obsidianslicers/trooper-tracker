<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Notice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NoticeTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_can_create_notice(): void
    {
        $subject = Notice::factory()->create();

        $this->assertInstanceOf(Notice::class, $subject);
        $this->assertDatabaseHas($subject->getTable(), [
            'id' => $subject->getKey(),
        ]);
    }
}