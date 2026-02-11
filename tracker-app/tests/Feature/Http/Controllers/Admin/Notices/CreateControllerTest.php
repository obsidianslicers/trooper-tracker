<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.notices.create'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_create_notice_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.create');
        $response->assertViewHas('notice');
    }

    public function test_invoke_administrator_can_access_create_form(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.create'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_moderator_can_access_create_form(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.notices.create'));

        // Assert
        $response->assertOk();
    }

    public function test_invoke_regular_trooper_cannot_access_create_form(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.notices.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_can_copy_existing_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $original = Notice::factory()->create([
            'organization_id' => $org->id,
            'title' => 'Original Notice',
        ]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.create', ['copy_id' => $original->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function ($notice)
        {
            return str_contains($notice->title, '(Copy)');
        });
    }

    public function test_invoke_preselects_organization_from_query(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.create', ['organization_id' => $org->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function ($notice) use ($org)
        {
            return $notice->organization_id == $org->id;
        });
    }

    public function test_invoke_moderator_can_only_copy_moderated_notices(): void
    {
        // Arrange
        $moderator = Trooper::factory()->asModerator()->create();
        $other_org = Organization::factory()->create();
        $other_notice = Notice::factory()->create([
            'organization_id' => $other_org->id,
        ]);

        // Act & Assert - moderator trying to copy notice from non-moderated org should fail
        $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['copy_id' => $other_notice->id]))
            ->assertNotFound();
    }
}
