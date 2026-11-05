<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Tests;

use App\Models\Organization;
use App\Plugins\PluginManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use TroopTracker\Plugins\Discord\Database\Seeders\FloridaGarrisonMentionSeeder;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

class FloridaGarrisonMentionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_saves_mentions_for_florida_garrison_squads(): void
    {
        $plugins = app(PluginManager::class);
        $plugins->enable('discord');
        $plugins->load($plugins->findOrFail('discord'));

        $garrison = Organization::factory()->asRegion()->create([Organization::NAME => 'Florida Garrison']);
        $makaze = Organization::factory()->asUnit()->create([
            Organization::NAME => 'Makaze Squad',
            Organization::PARENT_ID => $garrison->id,
        ]);
        $elsewhere = Organization::factory()->asUnit()->create([Organization::NAME => 'Squad 7']);

        $this->seed(FloridaGarrisonMentionSeeder::class);

        $this->assertSame('<@&914343663474200597>', DiscordOrganizationMention::forOrganization($makaze->id));
        $this->assertNull(DiscordOrganizationMention::forOrganization($elsewhere->id));
        $this->assertNull($makaze->fresh()->discord_mention);
    }
}
