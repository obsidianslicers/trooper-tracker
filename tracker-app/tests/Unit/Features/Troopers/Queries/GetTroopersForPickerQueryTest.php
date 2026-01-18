<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Models\Filters\TrooperFilter;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for GetTroopersForPickerQuery.
 *
 * Verifies:
 * - Query construction with filter
 * - Query construction with organization_id
 * - Query construction with both parameters
 * - Property access
 */
class GetTroopersForPickerQueryTest extends TestCase
{
    public function test_construct_with_filter_only(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET', ['search_term' => 'test']);
        $filter = new TrooperFilter($request);

        // Act
        $subject = new GetTroopersForPickerQuery($filter);

        // Assert
        $this->assertSame($filter, $subject->filter);
        $this->assertNull($subject->organization_id);
    }

    public function test_construct_with_organization_id(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET', []);
        $filter = new TrooperFilter($request);
        $organization_id = 123;

        // Act
        $subject = new GetTroopersForPickerQuery($filter, $organization_id);

        // Assert
        $this->assertSame($filter, $subject->filter);
        $this->assertSame($organization_id, $subject->organization_id);
    }

    public function test_construct_with_both_parameters(): void
    {
        // Arrange
        $request = Request::create('/test', 'GET', ['search_term' => 'test']);
        $filter = new TrooperFilter($request);
        $organization_id = 456;

        // Act
        $subject = new GetTroopersForPickerQuery($filter, $organization_id);

        // Assert
        $this->assertSame($filter, $subject->filter);
        $this->assertSame($organization_id, $subject->organization_id);
    }
}
