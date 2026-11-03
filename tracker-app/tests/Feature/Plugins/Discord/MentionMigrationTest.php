<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Discord;

use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class MentionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabling_copies_every_mention_then_drops_the_old_column(): void
    {
        $with_mention = $this->organizationWithOldMention(' <@&501> ');
        $empty = $this->organizationWithOldMention('');
        $without = $this->organizationWithOldMention(null);

        app(PluginManager::class)->enable('discord');

        $this->assertSame('<@&501>', DiscordOrganizationMention::forOrganization($with_mention->id));
        $this->assertNull(DiscordOrganizationMention::forOrganization($empty->id));
        $this->assertNull(DiscordOrganizationMention::forOrganization($without->id));
        $this->assertSame(1, DiscordOrganizationMention::query()->count());
        $this->assertFalse(Schema::hasColumn('tt_organizations', 'discord_mention'));
    }

    public function test_enabling_keeps_the_old_column_when_a_copy_does_not_match(): void
    {
        $organization = $this->organizationWithOldMention('<@&501>');
        $plugins = app(PluginManager::class);
        $migrations = $plugins->findOrFail('discord')->migrationsPath();
        $this->artisan('migrate', [
            '--path' => $migrations.'/2026_11_03_000001_create_discord_organization_mentions_table.php',
            '--realpath' => true,
        ]);
        DB::table('tt_discord_organization_mentions')->update(['mention' => '<@&999>']);

        try
        {
            $plugins->enable('discord');
            $this->fail('Enabling should stop when a copy does not match.');
        }
        catch (RuntimeException $e)
        {
            $this->assertStringContainsString((string) $organization->id, $e->getMessage());
        }

        $this->assertTrue(Schema::hasColumn('tt_organizations', 'discord_mention'));
        $this->assertSame('<@&501>', $this->oldMention($organization));
        $this->assertFalse($plugins->isEnabled('discord'));
    }

    public function test_uninstalling_puts_the_mentions_back_in_the_old_column(): void
    {
        $organization = $this->organizationWithOldMention('<@&501>');
        $edited = $this->organizationWithOldMention('<@&502>');
        $plugins = app(PluginManager::class);
        $plugins->enable('discord');
        DiscordOrganizationMention::store($edited->id, '<@&777>');

        $plugins->uninstall('discord');

        $this->assertFalse(Schema::hasTable('tt_discord_organization_mentions'));
        $this->assertSame('<@&501>', $this->oldMention($organization));
        $this->assertSame('<@&777>', $this->oldMention($edited));
    }

    public function test_enabling_again_after_uninstall_moves_the_mentions_back(): void
    {
        $organization = $this->organizationWithOldMention('<@&501>');
        $plugins = app(PluginManager::class);
        $plugins->enable('discord');
        $plugins->uninstall('discord');

        $plugins->enable('discord');

        $this->assertSame('<@&501>', DiscordOrganizationMention::forOrganization($organization->id));
        $this->assertFalse(Schema::hasColumn('tt_organizations', 'discord_mention'));
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
