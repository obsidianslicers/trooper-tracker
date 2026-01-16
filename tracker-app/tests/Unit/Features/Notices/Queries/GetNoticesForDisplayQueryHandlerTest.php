<?php

declare(strict_types=1);

namespace Tests\Unit\Features\Notices\Queries;

use App\Enums\NoticeType;
use App\Features\Notices\Queries\GetNoticesForDisplayQuery;
use App\Features\Notices\Queries\GetNoticesForDisplayQueryHandler;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for GetNoticesForDisplayQueryHandler.
 *
 * Verifies:
 * - Returns count of 0 and null notice when no notices exist
 * - Returns count and notice when exactly one notice exists
 * - Returns count and null when multiple notices exist
 * - Filters by unread_only when requested
 * - Uses Notice::visibleTo scope
 */
class GetNoticesForDisplayQueryHandlerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_zero_count_and_null_when_no_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $query = new GetNoticesForDisplayQuery($trooper, true);
        $subject = new GetNoticesForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals(0, $result['count']);
        $this->assertNull($result['notice']);
    }

    public function test_invoke_returns_notice_when_exactly_one_exists(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::TYPE => NoticeType::INFO,
        ]);

        $query = new GetNoticesForDisplayQuery($trooper, false);
        $subject = new GetNoticesForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals(1, $result['count']);
        $this->assertNotNull($result['notice']);
        $this->assertEquals($notice->id, $result['notice']->id);
    }

    public function test_invoke_returns_null_notice_when_multiple_exist(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        Notice::factory()->count(3)->active()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::TYPE => NoticeType::INFO,
        ]);

        $query = new GetNoticesForDisplayQuery($trooper, false);
        $subject = new GetNoticesForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert
        $this->assertEquals(3, $result['count']);
        $this->assertNull($result['notice']);
    }

    public function test_invoke_filters_by_unread_only(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        $unread_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::TYPE => NoticeType::INFO,
        ]);

        $read_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null,
            Notice::TYPE => NoticeType::INFO,
        ]);
        $read_notice->troopers()->attach($trooper->id, ['is_read' => true]);

        $query = new GetNoticesForDisplayQuery($trooper, true);
        $subject = new GetNoticesForDisplayQueryHandler();

        // Act
        $result = $subject($query);

        // Assert - should only count unread notice
        $this->assertEquals(1, $result['count']);
        $this->assertNotNull($result['notice']);
        $this->assertEquals($unread_notice->id, $result['notice']->id);
    }
}
