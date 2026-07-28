<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Merge;

use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;

/**
 * Merges the assignments of two troopers.
 * This command ensures that all assignments of the source trooper
 * are transferred to the target trooper, maintaining data integrity and consistency.
 *
 * @method static void call(Trooper $target_trooper, Trooper $source_trooper)
 */
final class MergeTrooperAssignments extends Message
{
    public function __construct(
        private readonly Trooper $target_trooper,
        private readonly Trooper $source_trooper,
    ) {}

    public function handle(): void
    {
        $source_assignments = TrooperAssignment::query()
            ->withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $this->source_trooper->id)
            ->orderBy(TrooperAssignment::ID)
            ->get();

        foreach ($source_assignments as $source_assignment)
        {
            $target_assignment = $this->getTargetAssignment($source_assignment);

            if ($target_assignment === null)
            {
                $source_assignment->trooper_id = $this->target_trooper->id;
                $source_assignment->save();

                continue;
            }

            $this->mergeAssignments($target_assignment, $source_assignment);
        }
    }

    private function getTargetAssignment(TrooperAssignment $source_assignment): ?TrooperAssignment
    {
        $target_assignment = TrooperAssignment::query()
            ->withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $this->target_trooper->id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $source_assignment->organization_id)
            ->first();

        return $target_assignment;
    }

    private function mergeAssignments(TrooperAssignment $target_assignment, TrooperAssignment $source_assignment): void
    {
        if ($target_assignment->trashed() && !$source_assignment->trashed())
        {
            $target_assignment->restore();
        }

        $source_assignment->forceDelete();

        $target_assignment->should_notify = $target_assignment->should_notify || $source_assignment->should_notify;
        $target_assignment->is_member = $target_assignment->is_member || $source_assignment->is_member;
        $target_assignment->is_moderator = $target_assignment->is_moderator || $source_assignment->is_moderator;
        $target_assignment->save();
    }
}
