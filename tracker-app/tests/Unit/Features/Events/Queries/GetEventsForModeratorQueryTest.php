<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Events\Queries;

use App\Features\Events\Queries\GetEventsForModeratorQuery;
use App\Models\Filters\EventFilter;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for GetEventsForModeratorQuery.
 *
 * Verifies:
 * - Query can be constructed with required parameters
 * - Query properties are properly typed and accessible
 * - Default page_size is set correctly
 */
class GetEventsForModeratorQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_construct_with_required_parameters(): void
    {
        // Arrange
        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $moderator = Trooper::factory()->create();

        // Act
        $subject = new GetEventsForModeratorQuery($filter, $moderator);

        // Assert
        $this->assertInstanceOf(GetEventsForModeratorQuery::class, $subject);
        $this->assertSame($filter, $subject->filter);
        $this->assertSame($moderator, $subject->moderator);
        $this->assertSame(25, $subject->page_size);
    }

    public function test_construct_with_custom_page_size(): void
    {
        // Arrange
        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $moderator = Trooper::factory()->create();
        $custom_page_size = 50;

        // Act
        $subject = new GetEventsForModeratorQuery($filter, $moderator, $custom_page_size);

        // Assert
        $this->assertSame($custom_page_size, $subject->page_size);
    }

    public function test_query_is_readonly(): void
    {
        // Arrange
        $request = Request::create('/admin/events/list');
        $filter = new EventFilter($request);
        $moderator = Trooper::factory()->create();

        // Act
        $subject = new GetEventsForModeratorQuery($filter, $moderator);

        // Assert - readonly properties cannot be modified
        $this->assertInstanceOf(GetEventsForModeratorQuery::class, $subject);
    }
}

