<?php

declare(strict_types=1);

namespace Tests\Unit\Messages\Organizations\Commands;

use App\Messages\Organizations\Commands\UpdateOrganization;
use App\Models\Organization;
use App\Plugins\PluginForms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UpdateOrganizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_renames_and_sets_the_roster_sync(): void
    {
        $organization = Organization::factory()->asOrganization()->create();

        $subject = new UpdateOrganization($organization, 'Renamed', [], true, 'rebel-legion');
        $subject->handle(app(PluginForms::class));

        $organization->refresh();
        $this->assertSame('Renamed', $organization->name);
        $this->assertSame('rebel-legion', $organization->synchronizer);
    }

    public function test_handle_leaves_the_roster_sync_when_the_caller_cannot_set_it(): void
    {
        $organization = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => '501st',
        ]);

        $subject = new UpdateOrganization($organization, 'Renamed', [], false, 'rebel-legion');
        $subject->handle(app(PluginForms::class));

        $this->assertSame('501st', $organization->fresh()->synchronizer);
    }

    public function test_handle_clears_an_empty_roster_sync(): void
    {
        $organization = Organization::factory()->asOrganization()->create([
            Organization::SYNCHRONIZER => '501st',
        ]);

        (new UpdateOrganization($organization, 'Club', [], true, ''))->handle(app(PluginForms::class));

        $this->assertNull($organization->fresh()->synchronizer);
    }

    public function test_handle_hands_the_form_to_the_plugins(): void
    {
        $organization = Organization::factory()->create();
        $validated = ['name' => 'Club', 'discord_role' => '123'];
        $plugin_forms = Mockery::mock(PluginForms::class);
        $plugin_forms->shouldReceive('saved')
            ->once()
            ->with(PluginForms::ORGANIZATION_UPDATE, $organization, $validated);

        (new UpdateOrganization($organization, 'Club', $validated))->handle($plugin_forms);
    }
}
