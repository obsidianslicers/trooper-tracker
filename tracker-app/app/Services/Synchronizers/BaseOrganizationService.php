<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\SynchronizerInterface;
use App\Enums\MembershipStatus;
use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Log;

abstract class BaseOrganizationService implements SynchronizerInterface
{
    private array $costume_cache = [];

    public function __construct(
        protected readonly Organization $organization,
        protected readonly GoogleService $google) {}

    public function run(): void
    {
        Log::info(__CLASS__.":{$this->organization->name} Starting synchronization");

        $this->synchronize();

        $this->updateOrganizationSync();

        Log::info(__CLASS__.":{$this->organization->name} Finished synchronization");
    }

    abstract protected function synchronize(): void;

    protected function getSheetRows(string $sheet_name, bool $skip_header = true): array
    {
        $sheet_id = $this->organization->sync_sheet_id;

        if (empty($sheet_id))
        {
            Log::info(__CLASS__.":{$this->organization->name} No 'sync_sheet_id' configured");

            return [];
        }

        // Costumes sheet columns expected: [legionId, costumename, costumeimage]
        $rows = $this->google->getSheet($sheet_id, $sheet_name);

        if (is_array($rows) === false)
        {
            Log::warning(__CLASS__.":{$this->organization->name} No rows found");

            return [];
        }

        if ($skip_header)
        {
            $rows = array_slice($rows, 1);
        }

        return $rows;
    }

    protected function updateOrganizationSync(): void
    {
        $this->organization->synchronized_at = now();
        $this->organization->save();
    }

    protected function getOrCreateOrganizationCostume(string $costume_name, ?string $prefix = null): OrganizationCostume
    {
        if (isset($this->costume_cache[$costume_name]))
        {
            return $this->costume_cache[$costume_name];
        }

        $costume = Costume::where(Costume::NAME, $costume_name)->first();

        if ($costume === null)
        {
            $costume = new Costume;
            $costume->name = $costume_name;
            $costume->save();
        }

        $org_costume = OrganizationCostume::query()
            ->where(OrganizationCostume::ORGANIZATION_ID, $this->organization->id)
            ->where(OrganizationCostume::COSTUME_ID, $costume->id)
            ->first();

        if ($org_costume === null)
        {
            $org_costume = new OrganizationCostume;
            $org_costume->organization_id = $this->organization->id;
            $org_costume->costume_id = $costume->id;
        }

        if ($prefix !== null)
        {
            $org_costume->prefix = $prefix;
        }

        $org_costume->synchronized_at = now();
        $org_costume->save();

        $this->costume_cache[$costume_name] = $org_costume;

        return $org_costume;
    }

    protected function getTrooper(string $identifier): ?Trooper
    {
        return $this->organization->troopers()
            ->wherePivot(TrooperOrganization::IDENTIFIER, $identifier)
            ->first();
    }

    protected function syncTrooperCostume(Trooper $trooper, OrganizationCostume $org_costume, array $attributes): void
    {
        $trooper_costume = TrooperCostume::withTrashed()
            ->firstOrNew([
                TrooperCostume::TROOPER_ID => $trooper->id,
                TrooperCostume::ORGANIZATION_COSTUME_ID => $org_costume->id,
            ]);

        if ($trooper_costume->exists && $trooper_costume->trashed())
        {
            // Unique index on (trooper_id, organization_costume_id) requires restoring
            // soft-deleted rows instead of creating new duplicates.
            $trooper_costume->restore();
        }

        foreach ($attributes as $key => $value)
        {
            $trooper_costume->{$key} = $value;
        }

        $trooper_costume->{TrooperCostume::SYNCHRONIZED_AT} = now();

        $trooper_costume->save();
    }

    protected function syncTrooperStatus(Trooper $trooper, MembershipStatus $status): void
    {
        $pivot = $trooper->pivot;

        $pivot->synchronized_at = now();
        $pivot->membership_status = $status;
        $pivot->save();
    }

    protected function cleanInput($value): mixed
    {
        if ($value === null)
        {
            return null;
        }

        $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return $value;
    }
}
