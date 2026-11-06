<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

/**
 * The Discord roles the Florida Garrison squads mention in event announcements.
 *
 * Run after the Florida Garrison seeder, with the Discord plugin enabled:
 *   php artisan db:seed \
 *     --class="TroopTracker\Plugins\Discord\Database\Seeders\FloridaGarrisonMentionSeeder"
 */
class FloridaGarrisonMentionSeeder extends Seeder
{
    private const MENTIONS = [
        'Everglades Squad' => '<@&914344158678900766>',
        'Makaze Squad' => '<@&914343663474200597>',
        'Tampa Bay Squad' => '<@&914344438472527912>',
        'Squad 7' => '<@&914344334776737822>',
        'Parjai Squad' => '<@&914344264253718568>',
    ];

    public function run(): void
    {
        $garrison = Organization::firstWhere(Organization::NAME, 'Florida Garrison');

        if ($garrison === null)
        {
            return;
        }

        $squads = Organization::query()
            ->where(Organization::PARENT_ID, $garrison->id)
            ->whereIn(Organization::NAME, array_keys(self::MENTIONS))
            ->get();

        foreach ($squads as $squad)
        {
            DiscordOrganizationMention::store($squad->id, self::MENTIONS[$squad->name]);
        }
    }
}
