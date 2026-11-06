<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Admin\Organizations;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateSubmitControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoke_updates_organization_and_redirects(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->create(['name' => 'Old Org']);

        $response = $this->actingAs($trooper)->post('/admin/organizations/'.$organization->id.'/update', [
            'name' => 'New Org',
        ]);

        $response->assertRedirect(route('admin.organizations.list'));
        $this->assertDatabaseHas('tt_organizations', [
            'id' => $organization->id,
            'name' => 'New Org',
        ]);
    }

    public function test_invoke_requires_authentication(): void
    {
        $organization = Organization::factory()->create();

        $response = $this->post('/admin/organizations/'.$organization->id.'/update', [
            'name' => 'New Org',
        ]);

        $response->assertRedirect(route('auth.login'));
    }

    public function test_invoke_saves_the_roster_sync_for_an_administrator(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $this->actingAs($trooper)->post($this->updateUrl($organization), [
            'name' => 'Club',
            'synchronizer' => 'rebel-legion',
        ])->assertSessionHasNoErrors();

        $this->assertSame('rebel-legion', $organization->fresh()->synchronizer);
    }

    public function test_invoke_clears_the_roster_sync(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => '501st',
        ]);

        $this->actingAs($trooper)->post($this->updateUrl($organization), [
            'name' => 'Club',
            'synchronizer' => '',
        ])->assertSessionHasNoErrors();

        $this->assertNull($organization->fresh()->synchronizer);
    }

    public function test_invoke_refuses_an_unknown_roster_sync(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create();

        $this->actingAs($trooper)->post($this->updateUrl($organization), [
            'name' => 'Club',
            'synchronizer' => 'made-up',
        ])->assertSessionHasErrors('synchronizer');
    }

    public function test_invoke_keeps_a_roster_sync_whose_plugin_is_off(): void
    {
        $trooper = Trooper::factory()->asAdministrator()->create();
        $organization = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => 'from-a-disabled-plugin',
        ]);

        $this->actingAs($trooper)->post($this->updateUrl($organization), [
            'name' => 'Club',
            'synchronizer' => 'from-a-disabled-plugin',
        ])->assertSessionHasNoErrors();

        $this->assertSame('from-a-disabled-plugin', $organization->fresh()->synchronizer);
    }

    public function test_invoke_ignores_the_roster_sync_from_a_moderator(): void
    {
        $organization = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => '501st',
        ]);
        $trooper = Trooper::factory()->asModerator()->create();
        $trooper->organizations()->attach($organization->id, ['identifier' => 'TK1']);

        $this->actingAs($trooper)->post($this->updateUrl($organization), [
            'name' => 'Club',
            'synchronizer' => 'saber-guild',
        ]);

        $this->assertSame('501st', $organization->fresh()->synchronizer);
    }

    private function updateUrl(Organization $organization): string
    {
        return '/admin/organizations/'.$organization->id.'/update';
    }
}
