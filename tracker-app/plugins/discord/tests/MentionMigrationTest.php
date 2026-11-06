<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Tests;

use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class MentionMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Core dropped this column; sites upgrading from before the plugin still have it.
        Schema::table('tt_organizations', function (Blueprint $table): void
        {
            $table->string('discord_mention', 128)->nullable();
        });
    }

    public function test_enabling_copies_mentions_and_leaves_the_core_column_alone(): void
    {
        $with_mention = $this->organizationWithOldMention(' <@&501> ');
        $empty = $this->organizationWithOldMention('');
        $without = $this->organizationWithOldMention(null);

        app(PluginManager::class)->enable('discord');

        $this->assertSame(
            '<@&501>',
            DiscordOrganizationMention::forOrganization($with_mention->id),
        );
        $this->assertNull(DiscordOrganizationMention::forOrganization($empty->id));
        $this->assertNull(DiscordOrganizationMention::forOrganization($without->id));
        $this->assertSame(1, DiscordOrganizationMention::query()->count());
        $this->assertSame(' <@&501> ', $this->oldMention($with_mention));
    }

    public function test_uninstalling_drops_only_the_plugin_table(): void
    {
        $organization = $this->organizationWithOldMention('<@&501>');
        $plugins = app(PluginManager::class);
        $plugins->enable('discord');

        $plugins->uninstall('discord');

        $this->assertFalse(Schema::hasTable('tt_discord_organization_mentions'));
        $this->assertSame('<@&501>', $this->oldMention($organization));
    }

    private function organizationWithOldMention(?string $mention): Organization
    {
        $organization = Organization::factory()->create();
        DB::table('tt_organizations')
            ->where('id', $organization->id)
            ->update(['discord_mention' => $mention]);

        return $organization;
    }

    private function oldMention(Organization $organization): ?string
    {
        return DB::table('tt_organizations')
            ->where('id', $organization->id)
            ->value('discord_mention');
    }
}
