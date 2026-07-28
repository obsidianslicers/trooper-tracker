<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\TrooperSearchMode;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Handler for retrieving troopers for picker/dropdown components.
 *
 * Processes GetTroopersForPickerQuery to return troopers based on:
 * - Organization filtering: Returns only troopers belonging to a specific organization
 * - Guardian/minor rule: Minors can only be returned for their assigned guardian
 * - Filter criteria: Applies search term and role filtering via TrooperFilter
 *
 * Results are ordered by relevance when a search term is present, otherwise by name.
 *
 * @implements QueryHandlerInterface<GetTroopersForPickerQuery>
 */
readonly class GetTroopersForPickerQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve troopers for picker components.
     *
     * Query behavior:
     * 1. Start with active troopers who have completed setup, ordered by name
     * 2. Apply guardian/minor visibility: include non-minors and minors only when the
     *    requesting trooper is their guardian
     * 3. If organization_id is set: Filter to troopers belonging to that organization
     * 4. If moderated_only is set: Filter to troopers moderated by the requesting trooper
     * 5. If filter has criteria: Apply search term and role filtering and return results
     * 6. If no filter criteria: Return empty collection
     *
     * @param  GetTroopersForPickerQuery  $message  The query containing filter and scope criteria
     * @return Collection<int, Trooper> Collection of filtered troopers, or empty if no filter applied
     */
    public function __invoke(object $message): mixed
    {
        $query = $this->buildBaseQuery($message);
        $has_filter = $message->filter->hasFilter();
        $is_friends_mode = $message->picker_mode == TrooperSearchMode::FRIENDS;

        if ($is_friends_mode && !$has_filter)
        {
            $query = $this->scopeToFriends($query, $message->trooper);
        }

        if ($has_filter)
        {
            $query = $query->filterWith($message->filter);
        }

        if (!$is_friends_mode && !$has_filter)
        {
            return collect([]);
        }

        $order_term = $message->filter->searchTermValue();
        $results = $this->resolveOrder($query, $order_term)->get();

        if ($results->isEmpty() && $has_filter && $message->filter->hasMultiWordSearchTerm())
        {
            $query = $this->buildBaseQuery($message)->filterWith($message->filter->useLooseSearch());
            $results = $this->resolveOrder($query, $order_term)->get();
        }

        return $results;
    }

    /**
     * Scope the query to only the requesting trooper's friends.
     *
     * @param  Builder<Trooper>  $query  The query to scope.
     * @param  Trooper  $trooper  The requesting trooper.
     * @return Builder<Trooper>
     */
    private function scopeToFriends(Builder $query, Trooper $trooper): Builder
    {
        $friend_ids = TrooperFriend::query()
            ->select(TrooperFriend::FRIEND_ID)
            ->where(TrooperFriend::TROOPER_ID, $trooper->id);

        return $query->whereIn(Trooper::ID, $friend_ids);
    }

    /**
     * Build the base trooper query shared by every branch of __invoke().
     *
     * @param  GetTroopersForPickerQuery  $message  The query containing filter and scope criteria
     * @return Builder<Trooper>
     */
    private function buildBaseQuery(object $message): Builder
    {
        $query = Trooper::active()
            ->whereNotNull(Trooper::SETUP_COMPLETED_AT)
            ->where(function ($q) use ($message) {
                $q->whereNull(Trooper::GUARDIAN_ID)
                    ->orWhere(Trooper::GUARDIAN_ID, $message->trooper->id);
            })
            ->with('organizations');

        if ($message->organization_id)
        {
            $query = $query->whereHas('organizations', function ($q) use ($message) {
                $q->where('tt_organizations.id', $message->organization_id);
            });
        }

        if ($message->moderated_only)
        {
            $query = $query->moderatedBy($message->trooper);
        }

        return $query;
    }

    /**
     * Order results by relevance to the search term, or by name when there isn't one.
     *
     * @param  Builder<Trooper>  $query  The query to order.
     * @param  string|null  $order_term  The search term to rank against, if any.
     * @return Builder<Trooper>
     */
    private function resolveOrder(Builder $query, ?string $order_term): Builder
    {
        return $order_term ? $query->orderByRelevance($order_term) : $query->orderBy(Trooper::DISPLAY_NAME);
    }
}
