<?php

declare(strict_types=1);

namespace App\Messages\Costumes\PageData;

use App\Messages\Costumes\Queries\SearchCostumes;
use App\Messages\Costumes\Resources\CostumeSearchResultResource;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The costumes matching a search (search.costumes), for the costume picker and the account
 * page's Add Costume, each with the clubs that recognise it.
 *
 * @method static AnonymousResourceCollection call(string $search_term, ?Trooper $trooper)
 */
final class SearchCostumesPageData extends Message
{
    public function __construct(
        private readonly string $search_term,
        private readonly ?Trooper $trooper,
    ) {}

    public function handle(): AnonymousResourceCollection
    {
        $costumes = SearchCostumes::call(
            search_term: $this->search_term,
            trooper: $this->trooper,
        );

        return CostumeSearchResultResource::collection($costumes);
    }
}
