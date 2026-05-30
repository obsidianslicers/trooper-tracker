<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use Illuminate\Console\Command;

class BackfillHandlerCostumesCommand extends Command
{
    protected $signature = 'tracker:backfill-handler-costumes';

    protected $description = 'Create missing OrganizationCostume and TrooperCostume records for Handler and Command Staff costumes. Safe to re-run.';

    public function handle(): int
    {
        $special = Costume::whereIn(Costume::NAME, [Costume::HANDLER, Costume::COMMAND_STAFF])->get();

        if ($special->isEmpty())
        {
            $this->error('No Handler or Command Staff costumes found. Aborting.');

            return self::FAILURE;
        }

        $this->info('Phase 1: OrganizationCostume records...');

        $org_created = 0;
        $org_ids = Organization::pluck(Organization::ID);

        foreach ($org_ids->chunk(200) as $chunk)
        {
            foreach ($chunk as $org_id)
            {
                foreach ($special as $costume)
                {
                    $record = OrganizationCostume::firstOrCreate([
                        OrganizationCostume::ORGANIZATION_ID => $org_id,
                        OrganizationCostume::COSTUME_ID => $costume->id,
                    ]);

                    if ($record->wasRecentlyCreated)
                    {
                        $org_created++;
                    }
                }
            }
        }

        $this->info("  Created {$org_created} OrganizationCostume record(s). Existing records untouched.");

        $this->info('Phase 2: TrooperCostume records...');

        $special_costume_ids = $special->pluck(Costume::ID);
        $trooper_created = 0;
        $trooper_skipped = 0;

        TrooperAssignment::where(TrooperAssignment::IS_MEMBER, true)
            ->with('trooper')
            ->chunkById(200, function ($assignments) use ($special_costume_ids, &$trooper_created, &$trooper_skipped) {
                foreach ($assignments as $assignment)
                {
                    $org_costumes = OrganizationCostume::whereIn(OrganizationCostume::COSTUME_ID, $special_costume_ids)
                        ->where(OrganizationCostume::ORGANIZATION_ID, $assignment->organization_id)
                        ->get();

                    foreach ($org_costumes as $org_costume)
                    {
                        $existing = $assignment->trooper
                            ->trooper_costumes()
                            ->withTrashed()
                            ->where(TrooperCostume::ORGANIZATION_COSTUME_ID, $org_costume->id)
                            ->exists();

                        if ($existing)
                        {
                            $trooper_skipped++;

                            continue;
                        }

                        $assignment->trooper->trooper_costumes()->create([
                            TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
                        ]);

                        $trooper_created++;
                    }
                }
            });

        $this->info("  Created {$trooper_created} TrooperCostume record(s). Skipped {$trooper_skipped} existing.");
        $this->newLine();
        $this->info('Backfill complete.');

        return self::SUCCESS;
    }
}
