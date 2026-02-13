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

class CreateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $org = Organization::factory()->create();

        // Act
        $response = $this->post(route('admin.notices.create'), [
            'organization_id' => $org->id,
            'title' => 'Test Notice',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Test message',
        ]);

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_creates_new_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.create'), [
            'organization_id' => $org->id,
            'title' => 'Important Update',
            'type' => NoticeType::WARNING->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'This is an important update for all troopers.',
        ]);

        // Assert
        $response->assertRedirect(route('admin.notices.list'));
        $this->assertDatabaseHas(Notice::class, [
            Notice::ORGANIZATION_ID => $org->id,
            Notice::TITLE => 'Important Update',
            Notice::TYPE => NoticeType::WARNING->value,
            Notice::MESSAGE => 'This is an important update for all troopers.',
        ]);
    }

    public function test_invoke_redirects_to_notices_list(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.create'), [
            'organization_id' => $org->id,
            'title' => 'Test Notice',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Test message',
        ]);

        // Assert
        $response->assertRedirect(route('admin.notices.list'));
    }

    public function test_invoke_administrator_can_create_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.create'), [
            'organization_id' => $org->id,
            'title' => 'Admin Notice',
            'type' => NoticeType::SUCCESS->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Created by administrator',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Notice::class, [
            Notice::TITLE => 'Admin Notice',
        ]);
    }

    public function test_invoke_moderator_can_create_notice(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $org = Organization::factory()->create();

        TrooperAssignment::factory()->create([
            TrooperAssignment::TROOPER_ID => $moderator->id,
            TrooperAssignment::ORGANIZATION_ID => $org->id,
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        // Act
        $response = $this->actingAs($moderator)->post(route('admin.notices.create'), [
            'organization_id' => $org->id,
            'title' => 'Moderator Notice',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Created by moderator',
        ]);

        // Assert
        $response->assertRedirect();
        $this->assertDatabaseHas(Notice::class, [
            Notice::TITLE => 'Moderator Notice',
        ]);
    }

    public function test_invoke_regular_trooper_cannot_create_notice(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->post(route('admin.notices.create'), [
            'organization_id' => $org->id,
            'title' => 'Test Notice',
            'type' => NoticeType::INFO->value,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(7)->toDateString(),
            'message' => 'Test message',
        ]);

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_validates_required_fields(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->post(route('admin.notices.create'), []);

        // Assert
        $response->assertSessionHasErrors(['title', 'type', 'starts_at', 'message']);
    }
}
