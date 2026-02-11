<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
    use RefreshDatabase;

    public function test_construct_with_filter_only(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $request = Request::create('/test', 'GET', ['search_term' => 'test']);
        $filter = new TrooperFilter($request);

        // Act
        $subject = new GetTroopersForPickerQuery($trooper, $filter, []);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($filter, $subject->filter);
        $this->assertNull($subject->organization_id);
        $this->assertFalse($subject->moderated_only);
    }

    public function test_construct_with_organization_id(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $request = Request::create('/test', 'GET', []);
        $filter = new TrooperFilter($request);

        // Act
        $subject = new GetTroopersForPickerQuery($trooper, $filter, [
            'organization_id' => '123',
        ]);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($filter, $subject->filter);
        $this->assertSame(123, $subject->organization_id);
        $this->assertFalse($subject->moderated_only);
    }

    public function test_construct_with_moderated_only(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $request = Request::create('/test', 'GET', ['search_term' => 'test']);
        $filter = new TrooperFilter($request);

        // Act
        $subject = new GetTroopersForPickerQuery($trooper, $filter, [
            'moderated_only' => true,
        ]);

        // Assert
        $this->assertSame($trooper, $subject->trooper);
        $this->assertSame($filter, $subject->filter);
        $this->assertTrue($subject->moderated_only);
        $this->assertNull($subject->organization_id);
    }
}
