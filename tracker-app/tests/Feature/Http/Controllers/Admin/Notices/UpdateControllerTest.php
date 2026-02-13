<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Arrange
        $notice = Notice::factory()->create();

        // Act
        $response = $this->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_update_notice_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.update');
        $response->assertViewHas('notice', $notice);
    }

    public function test_invoke_administrator_can_update_any_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertOk();
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
        $response = $this->actingAs($moderator)->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertOk();
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
        $response = $this->actingAs($moderator)->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_regular_trooper_cannot_update_notice(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();
        $notice = Notice::factory()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_passes_notice_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $notice = Notice::factory()->create(['title' => 'Specific Notice Title']);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.update', $notice));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function ($view_notice) use ($notice)
        {
            return $view_notice->id === $notice->id;
        });
    }
}
