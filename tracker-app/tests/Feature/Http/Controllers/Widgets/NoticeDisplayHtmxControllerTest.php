<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Widgets;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for NoticeDisplayHtmxController.
 *
 * Validates:
 * - Requires authentication
 * - Returns notice widget view
 * - Passes count and notice to view
 * - Shows single notice when exactly one exists
 * - Shows count when multiple notices exist
 */
class NoticeDisplayHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_returns_notice_widget_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('widgets.notice');
    }

    public function test_invoke_passes_count_and_notice_to_view(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('count');
        $response->assertViewHas('notice');
    }

    public function test_invoke_shows_single_notice_when_one_exists(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null, // Global notice visible to all
            Notice::TYPE => NoticeType::INFO,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $this->assertEquals(1, $response->viewData('count'));
        $this->assertNotNull($response->viewData('notice'));
        $this->assertEquals($notice->id, $response->viewData('notice')->id);
    }

    public function test_invoke_shows_count_when_multiple_notices_exist(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        Notice::factory()->count(3)->active()->create([
            Notice::ORGANIZATION_ID => null, // Global notices visible to all
            Notice::TYPE => NoticeType::INFO,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $this->assertEquals(3, $response->viewData('count'));
        $this->assertNull($response->viewData('notice'));
    }

    public function test_invoke_filters_to_unread_notices_only(): void
    {
        // Arrange
        $unread_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null, // Global notice visible to all
            Notice::TYPE => NoticeType::INFO,
        ]);

        $read_notice = Notice::factory()->active()->create([
            Notice::ORGANIZATION_ID => null, // Global notice visible to all
            Notice::TYPE => NoticeType::INFO,
        ]);

        $trooper = Trooper::factory()->asActive()->markAsRead($read_notice)->create();

        // Act
        $response = $this->actingAs($trooper)
            ->get(route('widgets.notices-htmx'));

        // Assert
        $response->assertOk();
        $this->assertEquals(1, $response->viewData('count'));
        $this->assertEquals($unread_notice->id, $response->viewData('notice')->id);
    }
}
