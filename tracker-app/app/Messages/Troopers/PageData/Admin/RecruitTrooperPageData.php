<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData\Admin;

use App\Messages\Organizations\Queries\GetModeratedOrganizationOptions;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;

/**
 * The Recruit Trooper form: the units the actor may add troopers to, and for each what its
 * club calls a member's identifier and whether one is required.
 *
 * @method static array call(Actor $actor)
 */
final class RecruitTrooperPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $organizations = Organization::moderatedBy($this->actor)->get();
        $clubs = Organization::whereIn(
            Organization::ID,
            $organizations->map(fn (Organization $organization) => $this->clubId($organization))->unique()
        )->get()->keyBy(Organization::ID);

        $identifiers = $organizations->mapWithKeys(function (Organization $organization) use ($clubs)
        {
            $club = $clubs[$this->clubId($organization)] ?? null;
            $display = $organization->identifier_display ?? $club?->identifier_display;
            $validation = $organization->identifier_validation ?? $club?->identifier_validation;

            return [$organization->id => [
                'label' => $display ?: __('admin.Member ID'),
                'is_required' => in_array('required', explode('|', (string) $validation), true),
            ]];
        });

        return [
            'organization_options' => GetModeratedOrganizationOptions::call(trooper: $this->actor),
            'identifiers' => $identifiers,
        ];
    }

    private function clubId(Organization $organization): int
    {
        $parts = array_filter(explode(Organization::NODE_PATH_SEP, $organization->node_path));

        return (int) reset($parts);
    }
}
