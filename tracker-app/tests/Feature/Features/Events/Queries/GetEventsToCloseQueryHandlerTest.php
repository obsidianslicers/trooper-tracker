<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsToCloseQuery;
use App\Features\Events\Queries\GetEventsToCloseQueryHandler;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventsToCloseQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_active_events_with_end_in_the_past(): void
    {
        $to_close = Event::factory()->withEventEnd(Carbon::parse('2026-03-01 00:00:00'))->create();
        Event::factory()->withEventEnd(now()->addDay())->create();
        Event::factory()->asClosed()->withEventEnd(Carbon::parse('2026-03-01 00:00:00'))->create();

        $subject = new GetEventsToCloseQueryHandler();

        $result = $subject(new GetEventsToCloseQuery());

        $this->assertCount(1, $result);
        $this->assertSame($to_close->id, $result->first()->id);
    }
}
