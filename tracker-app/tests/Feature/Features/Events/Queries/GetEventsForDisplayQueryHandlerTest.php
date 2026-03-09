<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForDisplayQuery;
use App\Features\Events\Queries\GetEventsForDisplayQueryHandler;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GetEventsForDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_only_upcoming_open_events(): void
    {
        $upcoming_open = Event::factory()->withEventStart(now()->addDays(3))->create();
        Event::factory()->asClosed()->withEventStart(now()->addDays(3))->create();
        Event::factory()->withEventStart(Carbon::parse('2020-01-01 00:00:00'))->create();

        $subject = new GetEventsForDisplayQueryHandler();

        $result = $subject(new GetEventsForDisplayQuery());

        $this->assertCount(1, $result);
        $this->assertSame($upcoming_open->id, $result->first()->id);
    }
}
