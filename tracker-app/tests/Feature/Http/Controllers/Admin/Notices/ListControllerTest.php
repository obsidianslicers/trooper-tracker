<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_requires_authentication(): void
    {
        // Act
        $response = $this->get(route('admin.notices.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_displays_notices_list_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.list');
        $response->assertViewHas('notices');
    }

    public function test_invoke_administrator_can_see_all_notices(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertSeeText($notice->title);
    }

    public function test_invoke_moderator_can_see_moderated_notices(): void
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
        $response = $this->actingAs($moderator)->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertSeeText($notice->title);
    }

    public function test_invoke_moderator_cannot_see_non_moderated_notices(): void
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

        $other_notice = Notice::factory()->create(['organization_id' => $other_org->id]);

        // Act
        $response = $this->actingAs($moderator)->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertDontSeeText($other_notice->title);
    }

    public function test_invoke_passes_scope_parameter_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.list', ['scope' => 'past']));

        // Assert
        $response->assertOk();
        $response->assertViewHas('scope', 'past');
    }

    public function test_invoke_filters_by_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org = Organization::factory()->create();
        $notice = Notice::factory()->create(['organization_id' => $org->id]);

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.list', ['organization_id' => $org->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('organization');
    }

    public function test_invoke_forbids_regular_trooper_access(): void
    {
        // Arrange
        $trooper = Trooper::factory()->asActive()->create();

        // Act
        $response = $this->actingAs($trooper)->get(route('admin.notices.list'));

        // Assert
        $response->assertForbidden();
    }
}
