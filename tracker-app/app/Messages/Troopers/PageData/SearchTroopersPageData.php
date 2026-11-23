<?php

declare(strict_types=1);

namespace App\Messages\Troopers\PageData;

use App\Enums\TrooperSearchMode;
use App\Messages\Troopers\Queries\SearchTroopers;
use App\Messages\Troopers\Resources\TrooperSearchResultResource;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The troopers matching a search (search.troopers), for the trooper picker.
 *
 * @method static AnonymousResourceCollection call(Actor $actor, ?string $search_term = null, int|null $organization_id = null, TrooperSearchMode $search_mode = TrooperSearchMode::NONE)
 */
final class SearchTroopersPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly ?string $search_term = null,
        private readonly ?int $organization_id = null,
        private readonly TrooperSearchMode $search_mode = TrooperSearchMode::NONE) {}

    public function handle(): AnonymousResourceCollection
    {
        $troopers = SearchTroopers::call(
            trooper: $this->actor,
            search_term: $this->search_term ?? '',
            organization_id: $this->organization_id,
            search_mode: $this->search_mode
        );

        return TrooperSearchResultResource::collection($troopers);
    }
}
