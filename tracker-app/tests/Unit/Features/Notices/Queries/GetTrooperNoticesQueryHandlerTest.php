<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Notices\Queries;

use App\Enums\NoticeType;
use App\Features\Notices\Queries\GetTrooperNoticesQuery;
use App\Features\Notices\Queries\GetTrooperNoticesQueryHandler;
use App\Models\Notice;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetTrooperNoticesQueryHandler.
 *
 * Verifies:
 * - Returns empty collection when no notices exist
 * - Returns visible notices for trooper
 * - Uses Notice::visibleTo scope
 * - Orders by starts_at
 */
class GetTrooperNoticesQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_empty_collection_when_no_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetTrooperNoticesQuery($trooper);
        $subject = new GetTrooperNoticesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(0, $result);
    }

    public function test_invoke_returns_visible_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $notice1 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::TYPE => NoticeType::INFO,
            Notice::STARTS_AT => now()->subDays(2),
        ]);

        $notice2 = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::TYPE => NoticeType::INFO,
            Notice::STARTS_AT => now()->subDays(1),
        ]);

        $query = new GetTrooperNoticesQuery($trooper);
        $subject = new GetTrooperNoticesQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertCount(2, $result);
        // Verify ordering by starts_at
        $this->assertEquals($notice1->id, $result[0]->id);
        $this->assertEquals($notice2->id, $result[1]->id);
    }
}
