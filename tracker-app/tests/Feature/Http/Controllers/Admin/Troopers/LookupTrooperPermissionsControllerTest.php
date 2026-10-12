<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Troopers;

use App\Enums\OauthProvider;
use App\Models\OauthLogin;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LookupTrooperPermissionsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_returns_role_moderated_organizations_and_forum_groups(): void
    {
        config([
            'services.xenforo.base_url' => 'https://xf.test',
            'services.xenforo.api_key' => 'key',
        ]);

        Http::fake([
            'https://xf.test/api/users/77' => Http::response(
                ['user' => ['secondary_group_ids' => [5, 20]]],
                200
            ),
        ]);

        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asModerator()->create();
        $organization = Organization::factory()->asOrganization()->withNodePath('100:')->create();
        TrooperAssignment::factory()->forTrooper($trooper)->forOrganization($organization)->create([
            TrooperAssignment::IS_MODERATOR => true,
        ]);
        OauthLogin::factory()->forTrooper($trooper)->create([
            OauthLogin::PROVIDER => OauthProvider::XENFORO,
            OauthLogin::PROVIDER_ID => '77',
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
            'has_forum_account' => true,
            'forum_group_ids' => [5, 20],
        ]);
    }

    public function test_invoke_reports_no_forum_account_when_xenforo_not_configured(): void
    {
        $moderator = Trooper::factory()->asModerator()->create();
        $trooper = Trooper::factory()->asMember()->create();

        $response = $this->actingAs($moderator)
            ->getJson(route('admin.troopers.permissions.lookup', $trooper));

        $response->assertOk();
        $response->assertJson([
            'trooper' => ['membership_role_label' => 'Regular Trooper'],
            'has_forum_account' => false,
            'forum_group_ids' => [],
        ]);
    }
}
