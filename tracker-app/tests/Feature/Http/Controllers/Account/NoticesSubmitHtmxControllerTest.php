<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Account;

use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for NoticesSubmitHtmxController.
 *
 * Verifies:
 * - Authenticated troopers can mark notices as read
 * - NoticeTrooper pivot record is created/updated correctly
 * - Returns correct HTML button for HTMX swap
 * - Prevents duplicate pivot records
 * - Unauthenticated users are redirected to login
 */
class NoticesSubmitHtmxControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_creates_notice_trooper_record(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_marks_notice_as_read(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $notice_trooper = NoticeTrooper::where(NoticeTrooper::NOTICE_ID, $notice->id)
            ->where(NoticeTrooper::TROOPER_ID, $trooper->id)
            ->first();

        $this->assertNotNull($notice_trooper);
        $this->assertTrue($notice_trooper->is_read);
    }

    public function test_invoke_does_not_create_duplicate_records(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Create initial record
        NoticeTrooper::create([
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => false,
        ]);

        // Act - submit again
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert - should only have 1 record
        $count = NoticeTrooper::where(NoticeTrooper::NOTICE_ID, $notice->id)
            ->where(NoticeTrooper::TROOPER_ID, $trooper->id)
            ->count();

        $this->assertEquals(1, $count);
    }

    public function test_invoke_updates_existing_unread_record_to_read(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Create unread record
        $notice_trooper = NoticeTrooper::create([
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => false,
        ]);

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $notice_trooper->refresh();
        $this->assertTrue($notice_trooper->is_read);
    }

    public function test_invoke_returns_html_button_response(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $response->assertSeeText('', false); // Response contains HTML
        $this->assertStringContainsString('button', $response->getContent());
        $this->assertStringContainsString('btn', $response->getContent());
    }

    public function test_invoke_returns_read_button_markup(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert - verify button contains envelope-open-text icon
        $this->assertStringContainsString('fa-envelope-open-text', $response->getContent());
    }

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->post(route('account.notices-htmx', $notice));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_only_marks_notice_for_authenticated_trooper(): void
    {
        // Arrange
        $trooper1 = Trooper::factory()->asActive()->create();
        $trooper2 = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act - trooper1 marks as read
        $response = $this->actingAs($trooper1)
            ->post(route('account.notices-htmx', $notice));

        // Assert - only trooper1 has read record
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper1->id,
            NoticeTrooper::IS_READ => true,
        ]);

        $this->assertDatabaseMissing(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper2->id,
        ]);
    }

    public function test_invoke_handles_active_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_handles_future_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->future()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_handles_past_notices(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->past()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_allows_trooper_to_mark_multiple_notices_as_read(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice1 = Notice::factory()->active()->create();
        $notice2 = Notice::factory()->active()->create();
        $notice3 = Notice::factory()->active()->create();

        // Act - mark all three notices as read
        $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice1));
        $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice2));
        $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice3));

        // Assert - all three notices should be marked as read
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice1->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice2->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice3->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_is_idempotent_when_called_multiple_times(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act - call three times
        $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));
        $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));
        $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert - should still only have one record
        $count = NoticeTrooper::where(NoticeTrooper::NOTICE_ID, $notice->id)
            ->where(NoticeTrooper::TROOPER_ID, $trooper->id)
            ->count();

        $this->assertEquals(1, $count);

        // Verify it's marked as read
        $notice_trooper = NoticeTrooper::where(NoticeTrooper::NOTICE_ID, $notice->id)
            ->where(NoticeTrooper::TROOPER_ID, $trooper->id)
            ->first();

        $this->assertTrue($notice_trooper->is_read);
    }

    public function test_invoke_uses_route_model_binding_for_notice(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act - use notice ID in route
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', ['notice' => $notice->id]));

        // Assert - should resolve notice via route model binding
        $this->assertDatabaseHas(NoticeTrooper::class, [
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => true,
        ]);
    }

    public function test_invoke_returns_success_response(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Act
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert
        $response->assertStatus(200);
    }

    public function test_invoke_preserves_existing_notice_trooper_timestamps(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->active()->create();

        // Create initial unread record
        $notice_trooper = NoticeTrooper::create([
            NoticeTrooper::NOTICE_ID => $notice->id,
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::IS_READ => false,
        ]);

        $original_created_at = $notice_trooper->created_at;

        // Act - mark as read after a delay
        sleep(1);
        $response = $this->actingAs($trooper)
            ->post(route('account.notices-htmx', $notice));

        // Assert - created_at should be preserved
        $notice_trooper->refresh();
        $this->assertEquals($original_created_at->timestamp, $notice_trooper->created_at->timestamp);
    }
}
