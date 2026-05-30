<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TrooperAssignment;
use Illuminate\Console\Command;

class FixTrooperAssignmentHierarchyCommand extends Command
{
    protected $signature = 'tracker:fix-trooper-assignment-hierarchy';

    protected $description = 'Set is_member = false on parent-level assignments where a more-specific child assignment already exists for the same trooper.';

    public function handle(): void
    {
        $fixed = 0;

        $assignments = TrooperAssignment::query()
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->with('organization')
            ->get()
            ->groupBy(TrooperAssignment::TROOPER_ID);

        foreach ($assignments as $trooper_id => $trooper_assignments)
        {
            foreach ($trooper_assignments as $assignment)
            {
                $node_path = $assignment->organization->node_path;

                $has_more_specific = $trooper_assignments->contains(function ($other) use ($node_path, $assignment) {
                    return $other->id !== $assignment->id
                        && str_starts_with($other->organization->node_path, $node_path)
                        && $other->organization->depth > $assignment->organization->depth;
                });

                if ($has_more_specific)
                {
                    $assignment->is_member = false;
                    $assignment->save();
                    $fixed++;
                }
            }
        }

        if ($fixed === 0)
        {
            $this->info('No dirty assignments found.');

            return;
        }

        $this->info("Fixed {$fixed} assignment(s).");
    }
}
