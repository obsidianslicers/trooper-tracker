<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Events\Commands;

use App\Messages\Events\Commands\RecordEventShareView;
use App\Models\Event;
use App\Models\EventShare;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordEventShareViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_counts_one_more_view(): void
    {
        $share = EventShare::factory()->forEvent(Event::factory()->create())->create(['view_count' => 2]);

        $subject = new RecordEventShareView($share);
        $subject->handle();

        $this->assertSame(3, $share->fresh()->view_count);
    }
}
