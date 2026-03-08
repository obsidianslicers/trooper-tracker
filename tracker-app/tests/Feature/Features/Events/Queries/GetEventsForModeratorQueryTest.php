<?php

declare(strict_types=1);

namespace Tests\Feature\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForModeratorQuery;
use App\Models\Filters\EventFilter;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Tests\TestCase;

class GetEventsForModeratorQueryTest extends TestCase
{
    public function test_construct_stores_filter_moderator_and_default_page_size(): void
    {
        $filter = new EventFilter(new Request());
        $moderator = new Trooper();

        $subject = new GetEventsForModeratorQuery($filter, $moderator);

        $this->assertSame($filter, $subject->filter);
        $this->assertSame($moderator, $subject->moderator);
        $this->assertSame(25, $subject->page_size);
    }

    public function test_construct_accepts_custom_page_size(): void
    {
        $subject = new GetEventsForModeratorQuery(new EventFilter(new Request()), new Trooper(), 50);

        $this->assertSame(50, $subject->page_size);
    }
}
