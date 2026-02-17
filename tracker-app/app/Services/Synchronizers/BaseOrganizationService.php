<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Contracts\SynchronizerInterface;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;
use Illuminate\Support\Facades\Log;

abstract class BaseOrganizationService implements SynchronizerInterface
{
    public function __construct(
        protected readonly Organization $organization,
        protected readonly GoogleService $google)
    {
    }

    public abstract function syncCostumes(): void;

    protected function getSheetRows(bool $skip_header = true): array
    {
        $sheet_id = $this->organization->sync_sheet_id;

        if (empty($sheet_id))
        {
            Log::info(__CLASS__ . " missing sync_sheet_id configured for organization {$this->organization->name}.");

            return [];
        }

        // Costumes sheet columns expected: [legionId, costumename, costumeimage]
        $rows = $this->google->getSheet($sheet_id, 'Costumes');

        if (is_array($rows) === false)
        {
            Log::warning(__CLASS__ . " no rows found in 'Costumes' sheet for organization {$this->organization->name} with sheet_id {$sheet_id}.");

            return [];
        }

        if ($skip_header)
        {
            $rows = array_slice($rows, 1);
        }

        return $rows;
    }

    protected function getOrganizationCostume(string $costume_name): OrganizationCostume
    {
        // find trooper by identifier on pivot
        $org_costume = $this->organization->organization_costumes()
            ->where(OrganizationCostume::NAME, $costume_name)
            ->first();

        if ($org_costume === null)
        {
            $org_costume = new OrganizationCostume();
            $org_costume->organization_id = $this->organization->id;
            $org_costume->name = $costume_name;
        }

        $org_costume->verified_at = now();
        $org_costume->save();

        return $org_costume;
    }

    protected function getTrooper(string $identifier): ?Trooper
    {
        return $this->organization->troopers()
            ->wherePivot(TrooperOrganization::IDENTIFIER, $identifier)
            ->first();
    }

    protected function syncTrooperCostume(Trooper $trooper, OrganizationCostume $org_costume, ?string $costume_image): void
    {
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

    public abstract function syncAllMembers(): void;

    public abstract function syncMember(string $identifier): void;

    protected function cleanInput($value): mixed
    {
        $value = filter_var($value, FILTER_SANITIZE_FULL_SPECIAL_CHARS);

        return $value;
    }
}