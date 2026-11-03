<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Discord\Messages;

use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use TroopTracker\Plugins\Discord\Models\DiscordOrganizationMention;

/**
 * Compares the old tt_organizations.discord_mention column with the plugin's
 * table, to confirm the copy is complete before the old column is dropped.
 *
 * "missing" rows had a value in the old column but have no row in the plugin
 * table. "mismatched" rows were copied with a different value and never edited
 * since. Both mean the copy is incomplete. "changed" rows differ because an
 * admin edited or cleared the mention after the copy, which is expected.
 *
 * @method static array call()
 */
final class VerifyMentionMigration extends Message
{
    /**
     * @return array{checked: int, missing: array<int, array>, mismatched: array<int, array>, changed: array<int, array>}
     */
    public function handle(): array
    {
        $report = ['checked' => 0, 'missing' => [], 'mismatched' => [], 'changed' => []];

        if (!Schema::hasColumn('tt_organizations', 'discord_mention'))
        {
            return $report;
        }

        $mentions = DiscordOrganizationMention::query()->get()->keyBy(DiscordOrganizationMention::ORGANIZATION_ID);

        foreach ($this->oldValues() as $organization)
        {
            $report['checked']++;
            $copy = $mentions->get($organization->id);
            $row = ['id' => $organization->id, 'name' => $organization->name, 'old' => $organization->discord_mention];

            if ($copy === null)
            {
                $report['missing'][] = $row;
            }
            elseif ($copy->mention !== trim((string) $organization->discord_mention))
            {
                $edited = $copy->updated_at?->greaterThan($copy->created_at) ?? false;
                $report[$edited ? 'changed' : 'mismatched'][] = [...$row, 'new' => $copy->mention];
            }
        }

        return $report;
    }

    private function oldValues(): iterable
    {
        return DB::table('tt_organizations')
            ->select(['id', 'name', 'discord_mention'])
            ->whereNotNull('discord_mention')
            ->where('discord_mention', '!=', '')
            ->orderBy('id')
            ->cursor();
    }
}
