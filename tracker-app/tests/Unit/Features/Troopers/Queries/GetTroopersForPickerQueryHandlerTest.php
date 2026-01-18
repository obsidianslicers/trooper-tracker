<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Features\Troopers\Queries\GetTroopersForPickerQueryHandler;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Unit tests for GetTroopersForPickerQueryHandler.
 *
 * Verifies:
 * - Returns all active troopers when no filters applied
 * - Filters by organization_id when provided
 * - Applies TrooperFilter when hasFilter() is true
 * - Combines organization and filter criteria
 * - Results are ordered by name
 * - Excludes inactive troopers
 */
class GetTroopersForPickerQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_all_active_troopers_when_no_filters(): void
    {
        // Arrange
        $active1 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice']);
        $active2 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob']);
        Trooper::factory()->asRetired()->create([Trooper::NAME => 'Retired']);

        $request = Request::create('/test', 'GET', []);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($filter);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertEquals('Alice', $result[0]->name);
        $this->assertEquals('Bob', $result[1]->name);
    }

    public function test_invoke_filters_by_organization_id(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob']);
        $trooper3 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Charlie']);

        $trooper1->organizations()->attach($organization->id, ['identifier' => 'TK-001']);
        $trooper2->organizations()->attach($organization->id, ['identifier' => 'TK-002']);

        $request = Request::create('/test', 'GET', []);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($filter, $organization->id);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        $this->assertTrue($result->contains($trooper1));
        $this->assertTrue($result->contains($trooper2));
        $this->assertFalse($result->contains($trooper3));
    }

    public function test_invoke_applies_filter_when_has_filter(): void
    {
        // Arrange
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice Smith']);
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob Jones']);
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Charlie Brown']);

        $request = Request::create('/test', 'GET', ['search_term' => 'Bob']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($filter);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Bob Jones', $result[0]->name);
    }

    public function test_invoke_combines_organization_and_filter(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice Smith']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Bob Smith']);
        $trooper3 = Trooper::factory()->asActive()->create([Trooper::NAME => 'Charlie Smith']);

        $trooper1->organizations()->attach($organization->id, ['identifier' => 'TK-001']);
        $trooper2->organizations()->attach($organization->id, ['identifier' => 'TK-002']);
        $trooper3->organizations()->attach($organization->id, ['identifier' => 'TK-003']);

        $request = Request::create('/test', 'GET', ['search_term' => 'Bob']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($filter, $organization->id);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Bob Smith', $result[0]->name);
    }

    public function test_invoke_orders_by_name(): void
    {
        // Arrange
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Zoe']);
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Alice']);
        Trooper::factory()->asActive()->create([Trooper::NAME => 'Mike']);

        $request = Request::create('/test', 'GET', []);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($filter);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals('Alice', $result[0]->name);
        $this->assertEquals('Mike', $result[1]->name);
        $this->assertEquals('Zoe', $result[2]->name);
    }
}
