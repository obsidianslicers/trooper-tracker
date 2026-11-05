<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Tests;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use TroopTracker\Plugins\Xenforo\Database\Seeders\FloridaGarrisonForumSeeder;
use TroopTracker\Plugins\Xenforo\Models\OrganizationForum;

class OrganizationForumTest extends TestCase
{
    use RefreshDatabase;
    use UsesXenforoPlugin;

    public function test_enabling_copies_the_organization_columns(): void
    {
        $organization = Organization::factory()->create();
        DB::table('tt_organizations')->where('id', $organization->id)->update([
            'related_forum' => 9,
            'related_forum_archive' => 107,
            'xenforo_group_active_id' => 44,
        ]);
        Organization::factory()->create();

        $this->enableXenforo();

        $forum = OrganizationForum::forOrganization($organization->id);
        $this->assertSame(9, $forum->event_forum_id);
        $this->assertSame(107, $forum->archive_forum_id);
        $this->assertSame(44, $forum->active_group_id);
        $this->assertNull($forum->retired_group_id);
        $this->assertSame(1, OrganizationForum::query()->count());
        $this->assertTrue(Schema::hasColumn('tt_organizations', 'related_forum'));
    }

    public function test_organization_form_shows_and_saves_the_forum_settings(): void
    {
        config([
            'plugins.xenforo.base_url' => 'https://forums.example.com',
            'plugins.xenforo.api_key' => 'abc',
        ]);
        $this->enableXenforo();
        $admin = Trooper::factory()->asAdministrator()->create();
        $organization = $this->createForumOrganization([OrganizationForum::EVENT_FORUM_ID => 9]);

        $this->actingAs($admin)
            ->get(route('admin.organizations.update', ['organization' => $organization]))
            ->assertOk()
            ->assertSee('Related Forum (Node ID):')
            ->assertSee('name="xenforo_event_forum_id"', false);

        $this->actingAs($admin)->post(route(
            'admin.organizations.update',
            ['organization' => $organization],
        ), [
            Organization::NAME => $organization->name,
            'xenforo_event_forum_id' => 12,
            'xenforo_archive_forum_id' => '',
            'xenforo_retired_group_id' => 30,
        ])->assertRedirect(route('admin.organizations.list'));

        $forum = OrganizationForum::forOrganization($organization->id);
        $this->assertSame(12, $forum->event_forum_id);
        $this->assertNull($forum->archive_forum_id);
        $this->assertSame(30, $forum->retired_group_id);
    }

    public function test_florida_garrison_seeder_sets_the_squad_forums(): void
    {
        $this->enableXenforo();
        $legion = Organization::factory()->create([Organization::NAME => '501st Legion']);
        $garrison = Organization::factory()->create([
            Organization::NAME => 'Florida Garrison',
            Organization::PARENT_ID => $legion->id,
        ]);
        $squad = Organization::factory()->create([
            Organization::NAME => 'Makaze Squad',
            Organization::PARENT_ID => $garrison->id,
        ]);

        $this->seed(FloridaGarrisonForumSeeder::class);

        $this->assertSame(1415, OrganizationForum::forOrganization($legion->id)->active_group_id);
        $this->assertSame(1429, OrganizationForum::forOrganization($legion->id)->retired_group_id);
        $this->assertSame(18, OrganizationForum::forOrganization($garrison->id)->active_group_id);
        $this->assertSame(8, OrganizationForum::forOrganization($squad->id)->event_forum_id);
        $this->assertSame(109, OrganizationForum::forOrganization($squad->id)->archive_forum_id);
    }
}
