<?php

declare(strict_types=1);

namespace Tests\Feature\Database\Migrations;

use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class DropDiscordMentionFromOrganizationsTest extends TestCase
{
    use RefreshDatabase;

    private Migration $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = require database_path(
            'migrations/2026_11_04_000001_drop_discord_mention_from_organizations.php'
        );

        Schema::table('tt_organizations', function (Blueprint $table): void
        {
            $table->string('discord_mention', 128)->nullable();
        });
    }

    public function test_up_copies_missing_mentions_then_drops_the_column(): void
    {
        $copied = $this->organizationWithOldMention('<@&501>');
        $plugins = app(PluginManager::class);
        $plugins->enable('discord');
        $plugins->load($plugins->findOrFail('discord'));
        DiscordOrganizationMention::store($copied->id, '<@&999>');
        $added_later = $this->organizationWithOldMention(' <@&502> ');

        $this->subject->up();

        $this->assertFalse(Schema::hasColumn('tt_organizations', 'discord_mention'));
        $this->assertSame('<@&999>', DiscordOrganizationMention::forOrganization($copied->id));
        $this->assertSame('<@&502>', DiscordOrganizationMention::forOrganization($added_later->id));
    }

    public function test_up_refuses_when_mentions_exist_and_the_plugin_was_never_enabled(): void
    {
        $this->organizationWithOldMention('<@&501>');

        try
        {
            $this->subject->up();
            $this->fail('The migration should refuse to drop the column.');
        }
        catch (RuntimeException)
        {
            $this->assertTrue(Schema::hasColumn('tt_organizations', 'discord_mention'));
        }
    }

    public function test_up_drops_the_column_when_no_mentions_were_set(): void
    {
        $this->organizationWithOldMention(null);

        $this->subject->up();

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
}
