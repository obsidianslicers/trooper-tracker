<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\UsesBadgeRequests;
use Tests\TestCase;

class LookupTrooperPermissionsControllerTest extends TestCase
{
    use RefreshDatabase;
    use UsesBadgeRequests;

    protected function setUp(): void
    {
        parent::setUp();

        $this->addBadgeRequests();
    }

    public function test_invoke_returns_role_moderated_organizations_and_extension_sections(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        $child = Organization::factory()->asRegion()->withParent($organization)->create();
        Organization::factory()->asOrganization()->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->create([
            TrooperAssignment::IS_MODERATOR => true,
        ]);
        $response = $this->actingAs($moderator)
            ->getJson(route('admin.troopers.permissions.lookup', $trooper));

        $response->assertOk();
        $response->assertJson([
            'trooper' => [
                'id' => $trooper->id,
                'membership_role' => 'moderator',
                'membership_role_label' => 'Moderator',
            ],
            'moderated_organization_ids' => [$organization->id],
            'extension_sections' => [[
                'key' => 'badges',
                'html' => '<input type="checkbox" name="extensions[badges][names][]" value="gold">',
            ]],
        ]);
        $this->assertEqualsCanonicalizing(
            [$organization->id, $child->id],
            $response->json('covered_organization_ids')
        );
    }

    public function test_invoke_leaves_out_empty_extension_sections(): void
    {
        $this->app->forgetInstance(\App\Plugins\PluginPermissionRequests::class);

        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($moderator)
            ->getJson(route('admin.troopers.permissions.lookup', $trooper));

        $response->assertOk();
        $response->assertJson([
            'trooper' => ['membership_role_label' => 'Regular Trooper'],
            'extension_sections' => [],
        ]);
    }
}
