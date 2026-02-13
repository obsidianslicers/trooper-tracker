<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $notice = Notice::factory()->create();

        // Act
        $response = $this->post(route('admin.notices.update', $notice), [
            'title' => 'Updated Title',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Updated message',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_updates_existing_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create([
            'organization_id' => $org->id,
            'title' => 'Original Title',
            'type' => NoticeType::INFO->value,
        ]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.update', $notice), [
            'title' => 'Updated Title',
            'type' => NoticeType::WARNING->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Updated message content',
        ]);

        // Assert
        $response->assertRedirect(route('admin.notices.list'));
        $this->assertDatabaseHas(Notice::class, [
            Notice::ID => $notice->id,
            Notice::TITLE => 'Updated Title',
            Notice::TYPE => NoticeType::WARNING->value,
            Notice::MESSAGE => 'Updated message content',
        ]);
    }

    public function test_invoke_redirects_to_notices_list(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.update', $notice), [
            'title' => 'Updated Notice',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Updated message',
        ]);

        // Assert
        $response->assertRedirect(route('admin.notices.list'));
    }

    public function test_invoke_administrator_can_update_any_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.update', $notice), [
            'title' => 'Admin Updated',
            'type' => NoticeType::SUCCESS->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Updated by administrator',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Notice::class, [
            Notice::ID => $notice->id,
            Notice::TITLE => 'Admin Updated',
        ]);
    }

    public function test_invoke_moderator_can_update_moderated_notice(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.notices.update', $notice), [
            'title' => 'Moderator Updated',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Updated by moderator',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Notice::class, [
            Notice::ID => $notice->id,
            Notice::TITLE => 'Moderator Updated',
        ]);
    }

    public function test_invoke_moderator_cannot_update_non_moderated_notice(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated_org = Organization::factory()->create();
        $other_org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $moderated_org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        $notice = Notice::factory()->create(['organization_id' => $other_org->id]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.notices.update', $notice), [
            'title' => 'Should Not Update',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Should not be updated',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_notice(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.notices.update', $notice), [
            'title' => 'Should Not Update',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Should not be updated',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $notice = Notice::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.update', $notice), []);

        // Assert
        $response->assertSessionHasErrors(['title', 'type', 'starts_at', 'message']);
    }
}
