<?php

declare(strict_types=1);

namespace App\Services\Synchronizers;

use App\Enums\MembershipStatus;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperOrganization;
use App\Services\GoogleService;

/**
 * Artisan command to calculate and store trooper achievements based on their event history.
 *
 * This command aggregates event data for each trooper, such as total troops,
 * volunteer hours, and funds raised, and then updates their corresponding
 * achievements in the database.
 */
class DroidBuildersService extends BaseOrganizationService
{
    public function __construct(private readonly GoogleService $google, Organization $organization)
    {
        parent::__construct($organization);
    }

    public function syncCostumes(): void
    {
    }

    public function syncAllMembers(): void
    {
    }

    public function syncMember(string $identifier): void
    {
    }

    private function updateTrooperStatus(Trooper $trooper): void
    {
    }
}
