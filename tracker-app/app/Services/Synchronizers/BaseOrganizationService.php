<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\SynchronizerInterface;
use App\Enums\MembershipStatus;
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
        protected readonly GoogleService $google)
    {
    }

    public abstract function synchronize(): void;

    protected function getSheetRows(bool $skip_header = true): array
    {
        $sheet_id = $this->organization->sync_sheet_id;

        if (empty($sheet_id))
        {
            Log::info(__CLASS__ . ":{$this->organization->name} No 'sync_sheet_id' configured");

            return [];
        }

        // Costumes sheet columns expected: [legionId, costumename, costumeimage]
        $rows = $this->google->getSheet($sheet_id, 'Costumes');

        if (is_array($rows) === false)
        {
            Log::warning(__CLASS__ . ":{$this->organization->name} No rows found");

            return [];
        }

        if ($skip_header)
        {
            $rows = array_slice($rows, 1);
        }

        return $rows;
    }

    protected function getOrCreateOrganizationCostume(string $costume_name, ?string $prefix = null): OrganizationCostume
    {
        if (isset($this->costume_cache[$costume_name]))
        {
            return $this->costume_cache[$costume_name];
        }

        Log::info(__CLASS__ . ":{$this->organization->name} Synchronizing Costume {$costume_name}");

        // find trooper by identifier on pivot
        $org_costume = $this->organization->organization_costumes()
            ->where(OrganizationCostume::NAME, $costume_name)
            ->first();

        if ($org_costume === null)
        {
            $org_costume = new OrganizationCostume();
            $org_costume->organization_id = $this->organization->id;
            $org_costume->name = $costume_name;
            $org_costume->prefix = $prefix;
        }

        $org_costume->verified_at = now();
        $org_costume->save();

        $this->costume_cache[$costume_name] = $org_costume;

        return $org_costume;
    }

    protected function getTrooper(string $identifier): ?Trooper
    {
        Log::info(__CLASS__ . ":{$this->organization->name} Getting Trooper {$identifier}");

        return $this->organization->troopers()
            ->wherePivot(TrooperOrganization::IDENTIFIER, $identifier)
            ->first();
    }

    protected function syncTrooperCostume(Trooper $trooper, OrganizationCostume $org_costume, ?string $costume_image): void
    {
        Log::info(__CLASS__ . ":{$this->organization->name} Synchronizing Trooper Costume {$org_costume->name} for Trooper {$trooper->display_name}");

        $trooper_costume = TrooperCostume::where(TrooperCostume::TROOPER_ID, $trooper->id)
            ->where(TrooperCostume::COSTUME_ID, $org_costume->id)
            ->first();

        if ($trooper_costume === null)
        {
            $trooper_costume = new TrooperCostume();
            $trooper_costume->trooper_id = $trooper->id;
            $trooper_costume->costume_id = $org_costume->id;
        }

        $trooper_costume->large_image_url = $costume_image;

        $trooper_costume->save();
    }

    protected function syncTrooperStatus(Trooper $trooper, MembershipStatus $status): void
    {
        Log::info(__CLASS__ . ":{$this->organization->name} Synchronizing Trooper Status {$status->name} for {$trooper->dsiplay_name}");

        $pivot = $trooper->pivot;

        $pivot->verified_at = now();
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