<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestPermissionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_shows_moderated_organizations_and_own_requests(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $moderated = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        Organization::factory()->asOrganization()->withNodePath('200:')->create();
        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($moderated)->create([
            TrooperAssignment::IS_MODERATOR => true,
        ]);

        PermissionRequest::factory()->requestedBy($moderator)->create();
        PermissionRequest::factory()->create();

        $response = $this->actingAs($moderator)->get(route('admin.troopers.permissions.request'));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/troopers/RequestPermissions')
            ->has('organizations', 1)
            ->where('organizations.0.id', $moderated->id)
            ->where('is_xenforo_configured', false)
            ->has('permission_requests', 1));
    }

    public function test_invoke_forbids_regular_members(): void
    {
        $member = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($member)->get(route('admin.troopers.permissions.request'));

        $response->assertForbidden();
    }
}
