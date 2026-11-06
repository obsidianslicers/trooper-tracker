<?php

declare(strict_types=1);

namespace Tests\Concerns;

use App\Models\Costume;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\Trooper;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;

/**
 * Two clubs: a club with a garrison under it, and an unrelated club. Helpers build
 * troopers, moderators and self-added costumes inside them.
 */
trait BuildsCostumeReviews
{
    protected Organization $club;

    protected Organization $garrison;

    protected Organization $other_club;

    protected function setUpClubs(): void
    {
        $this->club = Organization::factory()->asOrganization()->withName('501st Legion')->create();
        $this->garrison = Organization::factory()->asRegion()->withParent($this->club)
            ->withName('Florida Garrison')->create();
        $this->other_club = Organization::factory()->asOrganization()->withName('Rebel Legion')->create();
    }

    protected function memberOf(Organization $organization, ?Trooper $trooper = null): Trooper
    {
        $trooper ??= Trooper::factory()->asMember()->asActive()->create();

        TrooperAssignment::updateOrCreate(
            [
                TrooperAssignment::TROOPER_ID => $trooper->id,
                TrooperAssignment::ORGANIZATION_ID => $organization->id,
            ],
            [TrooperAssignment::IS_MEMBER => true],
        );

        return $trooper;
    }

    protected function moderatorOf(Organization $organization): Trooper
    {
        $moderator = Trooper::factory()->asModerator()->asActive()->create();

        TrooperAssignment::factory()->forTrooper($moderator)->forOrganization($organization)->asModerator()->create();

        return $moderator;
    }

    protected function costumeFor(
        Trooper $trooper,
        Organization $organization,
        bool $pending = true,
        string $name = 'Emperor Palpatine',
    ): TrooperCostume {
        $costume = Costume::firstOrCreate([Costume::NAME => $name]);

        $organization_costume = OrganizationCostume::firstOrCreate([
            OrganizationCostume::ORGANIZATION_ID => $organization->id,
            OrganizationCostume::COSTUME_ID => $costume->id,
        ]);

        $factory = TrooperCostume::factory()->forTrooper($trooper)->forOrganizationCostume($organization_costume);

        return ($pending ? $factory->pending() : $factory)->create();
    }
}
