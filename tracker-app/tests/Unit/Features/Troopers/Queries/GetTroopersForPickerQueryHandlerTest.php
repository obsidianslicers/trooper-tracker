<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Troopers\Queries;

use App\Features\Troopers\Queries\GetTroopersForPickerQuery;
use App\Features\Troopers\Queries\GetTroopersForPickerQueryHandler;
use App\Models\Filters\TrooperFilter;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
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
        $requester = Trooper::factory()->asActive()->create();
        $active1 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Alice']);
        $active2 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Bob']);
        Trooper::factory()->asRetired()->create([Trooper::DISPLAY_NAME => 'Retired']);

        $request = Request::create('/test', 'GET', ['search_term' => '%']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($requester, $filter, []);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->contains($active1));
        $this->assertTrue($result->contains($active2));
        $this->assertFalse($result->contains(Trooper::where(Trooper::DISPLAY_NAME, 'Retired')->first()));
    }

    public function test_invoke_filters_by_organization_id(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Alice']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Bob']);
        $trooper3 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Charlie']);

        $trooper1->organizations()->attach($organization->id, ['identifier' => 'TK-001']);
        $trooper2->organizations()->attach($organization->id, ['identifier' => 'TK-002']);

        $requester = Trooper::factory()->asActive()->create();
        $request = Request::create('/test', 'GET', ['search_term' => '%']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($requester, $filter, [
            'organization_id' => $organization->id,
        ]);
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
        Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Alice Smith']);
        Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Bob Jones']);
        Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Charlie Brown']);

        $requester = Trooper::factory()->asActive()->create();
        $request = Request::create('/test', 'GET', ['search_term' => 'Bob']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($requester, $filter, []);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Bob Jones', $result[0]->display_name);
    }

    public function test_invoke_combines_organization_and_filter(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $trooper1 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Alice Smith']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Bob Smith']);
        $trooper3 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Charlie Smith']);

        $trooper1->organizations()->attach($organization->id, ['identifier' => 'TK-001']);
        $trooper2->organizations()->attach($organization->id, ['identifier' => 'TK-002']);
        $trooper3->organizations()->attach($organization->id, ['identifier' => 'TK-003']);

        $requester = Trooper::factory()->asActive()->create();
        $request = Request::create('/test', 'GET', ['search_term' => 'Bob']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($requester, $filter, [
            'organization_id' => $organization->id,
        ]);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(1, $result);
        $this->assertEquals('Bob Smith', $result[0]->display_name);
    }

    public function test_invoke_orders_by_name(): void
    {
        // Arrange
        $requester = Trooper::factory()->asActive()->create();
        $zoe = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Zoe']);
        $alice = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Alice']);
        $mike = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Mike']);

        $request = Request::create('/test', 'GET', ['search_term' => '%']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($requester, $filter, []);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - check that results are properly ordered
        $names = $result->pluck(Trooper::DISPLAY_NAME)->values()->all();
        $this->assertContains('Alice', $names);
        $this->assertContains('Mike', $names);
        $this->assertContains('Zoe', $names);
        // Verify Alice comes before Mike and Mike before Zoe
        $this->assertLessThan(array_search('Mike', $names), array_search('Alice', $names));
        $this->assertLessThan(array_search('Zoe', $names), array_search('Mike', $names));
    }

    public function test_invoke_filters_to_moderated_troopers_when_requested(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->create();
        $other_organization = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $trooper1 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Alice']);
        $trooper2 = Trooper::factory()->asActive()->create([Trooper::DISPLAY_NAME => 'Bob']);

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper1->id,
            TrooperAssignment::ORGANIZATION_ID => $organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);
        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $trooper2->id,
            TrooperAssignment::ORGANIZATION_ID => $other_organization->id,
            TrooperAssignment::IS_MEMBER => true,
        ]);

        $request = Request::create('/test', 'GET', ['search_term' => '%']);
        $filter = new TrooperFilter($request);
        $query = new GetTroopersForPickerQuery($moderator, $filter, [
            'moderated_only' => true,
        ]);
        $subject = new GetTroopersForPickerQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertTrue($result->contains($trooper1));
        $this->assertFalse($result->contains($trooper2));
    }
}
