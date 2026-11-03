<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Moves each organization's Discord mention out of core's tt_organizations table
 * into a table the Discord plugin owns.
 *
 * Every non-empty value is copied here. The next migration checks each copy and
 * only then drops the old tt_organizations.discord_mention column.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tt_discord_organization_mentions', function (Blueprint $table)
        {
            $table->id();
            $table->foreignId('organization_id')
                ->unique()
                ->constrained('tt_organizations')
                ->cascadeOnDelete();
            $table->string('mention', 128);
            $table->timestamps();
        });

        $this->copyExistingMentions();
    }

    public function down(): void
    {
        Schema::dropIfExists('tt_discord_organization_mentions');
    }

    private function copyExistingMentions(): void
    {
        if (!Schema::hasColumn('tt_organizations', 'discord_mention'))
        {
            return;
        }

        DB::table('tt_organizations')
            ->select(['id', 'discord_mention'])
            ->whereNotNull('discord_mention')
            ->where('discord_mention', '!=', '')
            ->orderBy('id')
            ->chunk(500, function (Collection $organizations): void
            {
                $now = now();

                DB::table('tt_discord_organization_mentions')->insert(
                    $organizations->map(fn (object $organization): array => [
                        'organization_id' => $organization->id,
                        'mention' => trim((string) $organization->discord_mention),
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });
    }
};
