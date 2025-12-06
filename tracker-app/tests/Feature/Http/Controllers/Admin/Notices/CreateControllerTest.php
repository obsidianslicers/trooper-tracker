<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Enums\NoticeType;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_redirects_unauthenticated_users(): void
    {
        // Arrange
        // No user is authenticated

        // Act
        $response = $this->get(route('admin.notices.create'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbidden_for_unauthorized_users(): void
    {
        // Arrange
        $unauthorized_user = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($unauthorized_user)
            ->get(route('admin.notices.create'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_as_admin_returns_correct_view_and_data(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.notices.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.create');
        $response->assertViewHas('notice', function (Notice $notice)
        {
            return !$notice->exists && $notice->organization_id === null;
        });
        $response->assertViewHas('options', NoticeType::toDescriptions());
    }

    public function test_invoke_as_admin_with_organization_id_preloads_organization(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.notices.create', ['organization_id' => $organization->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function (Notice $notice) use ($organization)
        {
            return $notice->organization_id == $organization->id
                && $notice->organization->is($organization);
        });
    }

    public function test_invoke_as_moderator_with_moderated_organization_id_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization, moderator: true)
            ->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['organization_id' => $organization->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function (Notice $notice) use ($organization)
        {
            return $notice->organization_id == $organization->id
                && $notice->organization->is($organization);
        });
    }

    public function test_invoke_as_moderator_with_unmoderated_organization_id_is_not_found(): void
    {
        // Arrange
        $moderated_organization = Organization::factory()->create();
        $unmoderated_organization = Organization::factory()->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($moderated_organization, moderator: true)
            ->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['organization_id' => $unmoderated_organization->id]));

        // Assert
        $response->assertNotFound();
    }

    public function test_invoke_as_moderator_without_organization_id_succeeds(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization, moderator: true)
            ->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.create');
        $response->assertViewHas('notice', function (Notice $notice)
        {
            return !$notice->exists && $notice->organization_id === null;
        });
    }

    public function test_invoke_as_admin_can_copy_notice(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $source_notice = Notice::factory()->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.notices.create', ['copy_id' => $source_notice->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function (Notice $notice) use ($source_notice)
        {
            return !$notice->exists
                && $notice->title === $source_notice->title
                && $notice->message === $source_notice->message
                && $notice->organization_id === $source_notice->organization_id;
        });
    }

    public function test_invoke_as_moderator_can_copy_moderated_notice(): void
    {
        // Arrange
        $organization = Organization::factory()->create();
        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($organization, moderator: true)
            ->create();

        $source_notice = Notice::factory()->for($organization)->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['copy_id' => $source_notice->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notice', function (Notice $notice) use ($source_notice)
        {
            return !$notice->exists && $notice->title === $source_notice->title;
        });
    }

    public function test_invoke_as_moderator_cannot_copy_unmoderated_notice(): void
    {
        // Arrange
        $moderated_org = Organization::factory()->create();
        $unmoderated_org = Organization::factory()->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($moderated_org, moderator: true)
            ->create();

        $source_notice = Notice::factory()->for($unmoderated_org)->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.create', ['copy_id' => $source_notice->id]));

        // Assert
        $response->assertNotFound();
    }
}