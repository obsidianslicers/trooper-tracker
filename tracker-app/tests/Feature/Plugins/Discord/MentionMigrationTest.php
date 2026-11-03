<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\Discord;

use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class MentionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabling_the_plugin_copies_existing_mentions_and_keeps_the_old_column(): void
    {
        $with_mention = Organization::factory()->create([Organization::DISCORD_MENTION => ' <@&501> ']);
        $empty = Organization::factory()->create([Organization::DISCORD_MENTION => '']);
        $without = Organization::factory()->create([Organization::DISCORD_MENTION => null]);

        $this->enableDiscord();

        $this->assertSame('<@&501>', DiscordOrganizationMention::forOrganization($with_mention->id));
        $this->assertNull(DiscordOrganizationMention::forOrganization($empty->id));
        $this->assertNull(DiscordOrganizationMention::forOrganization($without->id));
        $this->assertSame(1, DiscordOrganizationMention::query()->count());
        $this->assertTrue(Schema::hasColumn('tt_organizations', 'discord_mention'));
        $this->assertSame(' <@&501> ', $with_mention->fresh()->discord_mention);
    }

    public function test_verify_passes_when_every_mention_was_copied(): void
    {
        Organization::factory()->create([Organization::DISCORD_MENTION => '<@&501>']);
        $this->enableDiscord();

        $this->artisan('discord:verify-mentions')
            ->expectsOutput('Checked 1 organizations with a Discord mention in the old column.')
            ->expectsOutput('Every mention is in the Discord plugin. The old column can be dropped safely.')
            ->assertSuccessful();
    }

    public function test_verify_treats_later_admin_edits_as_expected(): void
    {
        $organization = Organization::factory()->create([Organization::DISCORD_MENTION => '<@&501>']);
        $this->enableDiscord();
        $this->travel(1)->minutes();

        DiscordOrganizationMention::store($organization->id, '');

        $this->artisan('discord:verify-mentions')->assertSuccessful();
    }

    public function test_verify_fails_when_a_mention_is_missing_or_wrong(): void
    {
        $missing = Organization::factory()->create([Organization::DISCORD_MENTION => '<@&501>']);
        $wrong = Organization::factory()->create([Organization::DISCORD_MENTION => '<@&502>']);
        $this->enableDiscord();

        DiscordOrganizationMention::query()->where('organization_id', $missing->id)->delete();
        DB::table('tt_discord_organization_mentions')
            ->where('organization_id', $wrong->id)
            ->update(['mention' => '<@&999>']);

        $this->artisan('discord:verify-mentions')
            ->expectsOutput('1 are missing from the Discord plugin.')
            ->expectsOutput('1 were copied with a different value.')
            ->expectsOutput('Do not drop tt_organizations.discord_mention yet.')
            ->assertFailed();
    }

    private function enableDiscord(): void
    {
        $plugins = app(PluginManager::class);
        $plugins->enable('discord');
        $plugins->load($plugins->findOrFail('discord'));

        // Artisan was already started by the migration, so rebuild it to pick up
        // the plugin's command, as a fresh "php artisan" process would.
        $this->app->make(Kernel::class)->setArtisan(null);
    }
}
