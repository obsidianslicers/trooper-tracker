<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MimicTrooperPermissionsCommand extends Command
{
    private const SNAPSHOT_DIR = 'permission-mimics';

    protected $signature = 'tracker:mimic-trooper-permissions
        {target_trooper_id : ID of the trooper whose permissions will be changed}
        {source_trooper_id? : ID of the trooper whose permissions will be copied}
        {--revert : Revert the target trooper to their previously snapshotted permissions}
        {--force : Overwrite an existing snapshot for the target trooper}';

    protected $description = 'Copy role/assignment permissions from one trooper to another and allow reverting from a snapshot.';

    public function handle(): int
    {
        $target_id = (int) $this->argument('target_trooper_id');

        if ($target_id <= 0)
        {
            $this->error('Please provide a valid target_trooper_id.');

            return self::FAILURE;
        }

        if ($this->option('revert'))
        {
            return $this->revertPermissions($target_id);
        }

        $source_id = (int) $this->argument('source_trooper_id');

        if ($source_id <= 0)
        {
            $this->error('Please provide a valid source_trooper_id, or use --revert.');

            return self::FAILURE;
        }

        if ($target_id === $source_id)
        {
            $this->error('target_trooper_id and source_trooper_id must be different troopers.');

            return self::FAILURE;
        }

        $target = Trooper::find($target_id);
        $source = Trooper::find($source_id);

        if (! $target)
        {
            $this->error("Target trooper with ID {$target_id} was not found.");

            return self::FAILURE;
        }

        if (! $source)
        {
            $this->error("Source trooper with ID {$source_id} was not found.");

            return self::FAILURE;
        }

        $snapshot_path = $this->snapshotPath($target->id);

        if (! $this->option('force') && Storage::disk('local')->exists($snapshot_path))
        {
            $this->error("A snapshot already exists for trooper {$target->id}. Revert first or pass --force.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($target, $source, $snapshot_path): void {
            $snapshot = [
                'version' => 1,
                'captured_at' => now()->toISOString(),
                'target_trooper_id' => $target->id,
                'source_trooper_id' => $source->id,
                'membership_role' => $target->membership_role->value,
                'assignments' => $this->getAssignmentPayload($target),
            ];

            Storage::disk('local')->put($snapshot_path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));

            $target->membership_role = $source->membership_role;
            $target->save();

            $assignment_summary = $this->syncAssignments($target, collect($this->getAssignmentPayload($source)));

            $this->info("Snapshot saved: {$snapshot_path}");
            $this->info("Mimicked permissions from #{$source->id} ({$source->display_name}) to #{$target->id} ({$target->display_name}).");
            $this->line('Assignment changes: '
                ."created {$assignment_summary['created']}, "
                ."updated {$assignment_summary['updated']}, "
                ."restored {$assignment_summary['restored']}, "
                ."removed {$assignment_summary['removed']}.");
        });

        return self::SUCCESS;
    }

    private function revertPermissions(int $target_id): int
    {
        $target = Trooper::find($target_id);

        if (! $target)
        {
            $this->error("Target trooper with ID {$target_id} was not found.");

            return self::FAILURE;
        }

        $snapshot_path = $this->snapshotPath($target->id);

        if (! Storage::disk('local')->exists($snapshot_path))
        {
            $this->error("No snapshot found for trooper {$target->id}. Nothing to revert.");

            return self::FAILURE;
        }

        $snapshot_json = Storage::disk('local')->get($snapshot_path);
        $snapshot = json_decode($snapshot_json, true);

        if (! is_array($snapshot)
            || ($snapshot['target_trooper_id'] ?? null) !== $target->id
            || ! isset($snapshot['membership_role'])
            || ! is_array($snapshot['assignments'] ?? null))
        {
            $this->error("Snapshot file {$snapshot_path} is invalid and cannot be used for revert.");

            return self::FAILURE;
        }

        DB::transaction(function () use ($target, $snapshot, $snapshot_path): void {
            $target->membership_role = $snapshot['membership_role'];
            $target->save();

            $assignment_summary = $this->syncAssignments($target, collect($snapshot['assignments']));

            Storage::disk('local')->delete($snapshot_path);

            $this->info("Reverted permissions for #{$target->id} ({$target->display_name}).");
            $this->line('Assignment changes: '
                ."created {$assignment_summary['created']}, "
                ."updated {$assignment_summary['updated']}, "
                ."restored {$assignment_summary['restored']}, "
                ."removed {$assignment_summary['removed']}.");
            $this->info("Snapshot removed: {$snapshot_path}");
        });

        return self::SUCCESS;
    }

    /**
     * @return array<int,array{organization_id:int,should_notify:bool,is_member:bool,is_moderator:bool}>
     */
    private function getAssignmentPayload(Trooper $trooper): array
    {
        return TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $trooper->id)
            ->get([
                TrooperAssignment::ORGANIZATION_ID,
                TrooperAssignment::SHOULD_NOTIFY,
                TrooperAssignment::IS_MEMBER,
                TrooperAssignment::IS_MODERATOR,
            ])
            ->map(fn (TrooperAssignment $assignment): array => [
                'organization_id' => (int) $assignment->{TrooperAssignment::ORGANIZATION_ID},
                'should_notify' => (bool) $assignment->{TrooperAssignment::SHOULD_NOTIFY},
                'is_member' => (bool) $assignment->{TrooperAssignment::IS_MEMBER},
                'is_moderator' => (bool) $assignment->{TrooperAssignment::IS_MODERATOR},
            ])
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int,array{organization_id:int,should_notify:bool,is_member:bool,is_moderator:bool}>  $desired_assignments
     * @return array{created:int,updated:int,restored:int,removed:int}
     */
    private function syncAssignments(Trooper $target, Collection $desired_assignments): array
    {
        $created = 0;
        $updated = 0;
        $restored = 0;

        $desired_by_org_id = $desired_assignments
            ->keyBy(fn (array $assignment): int => (int) $assignment['organization_id']);

        $existing_by_org_id = TrooperAssignment::withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $target->id)
            ->get()
            ->keyBy(TrooperAssignment::ORGANIZATION_ID);

        // Remove assignments not in the desired list first so the observer's hierarchy
        // conflict check doesn't see them when we restore/create desired assignments.
        $desired_org_ids = $desired_by_org_id->keys()->map(fn (int|string $id): int => (int) $id)->all();

        $to_remove = TrooperAssignment::query()
            ->where(TrooperAssignment::TROOPER_ID, $target->id)
            ->when(! empty($desired_org_ids), fn ($query) => $query->whereNotIn(TrooperAssignment::ORGANIZATION_ID, $desired_org_ids))
            ->when(empty($desired_org_ids), fn ($query) => $query)
            ->get();

        $removed = $to_remove->count();

        foreach ($to_remove as $assignment)
        {
            $assignment->delete();
        }

        foreach ($desired_by_org_id as $organization_id => $desired)
        {
            /** @var TrooperAssignment|null $assignment */
            $assignment = $existing_by_org_id->get($organization_id);

            if (! $assignment)
            {
                TrooperAssignment::create([
                    TrooperAssignment::TROOPER_ID => $target->id,
                    TrooperAssignment::ORGANIZATION_ID => (int) $desired['organization_id'],
                    TrooperAssignment::SHOULD_NOTIFY => (bool) $desired['should_notify'],
                    TrooperAssignment::IS_MEMBER => (bool) $desired['is_member'],
                    TrooperAssignment::IS_MODERATOR => (bool) $desired['is_moderator'],
                ]);

                $created++;

                continue;
            }

            $was_trashed = $assignment->trashed();

            if ($was_trashed)
            {
                $assignment->restore();
            }

            $assignment->fill([
                TrooperAssignment::SHOULD_NOTIFY => (bool) $desired['should_notify'],
                TrooperAssignment::IS_MEMBER => (bool) $desired['is_member'],
                TrooperAssignment::IS_MODERATOR => (bool) $desired['is_moderator'],
            ]);

            if ($assignment->isDirty())
            {
                $assignment->save();
                $updated++;
            }

            if ($was_trashed)
            {
                $restored++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'restored' => $restored,
            'removed' => $removed,
        ];
    }

    private function snapshotPath(int $target_trooper_id): string
    {
        return self::SNAPSHOT_DIR."/trooper-{$target_trooper_id}.json";
    }
}
