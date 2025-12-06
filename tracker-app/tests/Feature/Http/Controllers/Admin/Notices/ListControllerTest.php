<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Notices;

use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class ListControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_redirects_unauthenticated_users(): void
    {
        // Arrange
        // No user is authenticated

        // Act
        $response = $this->get(route('admin.notices.list'));

        // Assert
        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_forbidden_for_unauthorized_users(): void
    {
        // Arrange
        $unauthorized_user = Trooper::factory()->create();

        // Act
        $response = $this->actingAs($unauthorized_user)
            ->get(route('admin.notices.list'));

        // Assert
        $response->assertForbidden();
    }

    public function test_invoke_as_admin_shows_all_active_notices(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Notice::factory()->count(3)->active()->create(); // Default scope
        Notice::factory()->count(2)->past()->create();   // Should not be included

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertViewIs('pages.admin.notices.list');
        $response->assertViewHas('notices', function (LengthAwarePaginator $notices)
        {
            return $notices->total() === 3;
        });
        $response->assertViewHas('organization', null);
        $response->assertViewHas('scope', 'active');
    }

    public function test_invoke_as_moderator_shows_only_moderated_notices(): void
    {
        // Arrange
        $moderated_org = Organization::factory()->create();
        $unmoderated_org = Organization::factory()->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($moderated_org, moderator: true)
            ->create();

        $moderated_notice = Notice::factory()->active()->for($moderated_org)->create();
        $unmoderated_notice = Notice::factory()->active()->for($unmoderated_org)->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notices', function (LengthAwarePaginator $notices) use ($moderated_notice)
        {
            return $notices->total() === 1 && $notices->first()->is($moderated_notice);
        });
    }

    public function test_invoke_filters_by_organization_id(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        $org1 = Organization::factory()->create();
        $org2 = Organization::factory()->create();

        $notice1 = Notice::factory()->active()->for($org1)->create();
        Notice::factory()->active()->for($org2)->create();

        // Act
        $response = $this->actingAs($admin)
            ->get(route('admin.notices.list', ['organization_id' => $org1->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notices', function (LengthAwarePaginator $notices) use ($notice1)
        {
            return $notices->total() === 1 && $notices->first()->is($notice1);
        });
        $response->assertViewHas('organization', function (Organization $organization) use ($org1)
        {
            return $organization->is($org1);
        });
    }

    public function test_invoke_as_moderator_with_unmoderated_organization_id_shows_no_notices(): void
    {
        // Arrange
        $moderated_org = Organization::factory()->create();
        $unmoderated_org = Organization::factory()->create();

        $moderator = Trooper::factory()
            ->asModerator()
            ->withAssignment($moderated_org, moderator: true)
            ->create();

        Notice::factory()->active()->for($moderated_org)->create();
        Notice::factory()->active()->for($unmoderated_org)->create();

        // Act
        $response = $this->actingAs($moderator)
            ->get(route('admin.notices.list', ['organization_id' => $unmoderated_org->id]));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notices', fn(LengthAwarePaginator $n) => $n->total() === 0);
    }

    public function test_invoke_passes_scope_from_query_to_view(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();

        Notice::factory()->active()->create();
        Notice::factory()->past()->create();
        Notice::factory()->future()->create();

        // Act
        $response_all = $this->actingAs($admin)->get(route('admin.notices.list', ['scope' => 'active']));
        $response_past = $this->actingAs($admin)->get(route('admin.notices.list', ['scope' => 'past']));
        $response_future = $this->actingAs($admin)->get(route('admin.notices.list', ['scope' => 'future']));

        // Assert
        $response_all->assertOk();
        $response_all->assertViewHas('scope', 'active');
        $response_all->assertViewHas('notices', fn(LengthAwarePaginator $n) => $n->total() === 1);

        $response_past->assertOk();
        $response_past->assertViewHas('scope', 'past');
        $response_past->assertViewHas('notices', fn(LengthAwarePaginator $n) => $n->total() === 1);

        $response_future->assertOk();
        $response_future->assertViewHas('scope', 'future');
        $response_future->assertViewHas('notices', fn(LengthAwarePaginator $n) => $n->total() === 1);
    }

    public function test_invoke_paginates_results(): void
    {
        // Arrange
        $admin = Trooper::factory()->asAdministrator()->create();
        Notice::factory()->count(20)->active()->create();

        // Act
        $response = $this->actingAs($admin)->get(route('admin.notices.list'));

        // Assert
        $response->assertOk();
        $response->assertViewHas('notices', fn(LengthAwarePaginator $n) => $n->count() === 15 && $n->total() === 20);
    }
}