<?php

declare(strict_types=1);

namespace App\Messages\Costumes\PageData;

use App\Messages\Costumes\Queries\SearchCostumes;
use App\Models\Trooper;
use App\Models\Costume;
use App\Models\Organization;
use Hyperdrive\Message;

/**
 * Retrieves search results for costumes based on the provided search term, organization, and other filters.
 *
 * This page data message responds with a list of costumes matching the search criteria, formatted for frontend consumption.
 *
 * @method static array call(string $search_term, Trooper $trooper)
 */
final class SearchCostumesPageData extends Message
{
    /**
     * Summary of __construct
     *
     * @param  Trooper  $trooper
     */
    public function __construct(
        private readonly string $search_term,
        private readonly Trooper|null $trooper,
    ) {
    }

    /**
     * Retrieves application configuration as a nested associative array.
     *
     * @return array Configuration array with auth provider status, URLs, features, and localization settings
     */
    public function handle(): array
    {
        $costumes = SearchCostumes::call(
            search_term: $this->search_term,
            trooper: $this->trooper,
        );

        $data = $costumes->map(fn(Costume $costume) => [
            Costume::ID => $costume->id,
            Costume::NAME => $costume->name,
            'organizations' => $costume->organization_costumes->map(fn($organization_costume) => [
                Organization::ID => $organization_costume->organization->id,
                Organization::NAME => $organization_costume->organization->name,
            ])->toArray(),
        ])->toArray();

        return $data;
    }
}
