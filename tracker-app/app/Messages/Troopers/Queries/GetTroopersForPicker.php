<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\TrooperSearchMode;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Hyperdrive\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Query to retrieve troopers for use in picker/dropdown components.
 *
 * Supports filtering troopers by:
 * - Organization membership (specific organization)
 * - Search criteria (name, email, TK number via TrooperFilter)
 * - Role filters (admin, moderator, etc. via TrooperFilter)
 *
 * Results are ordered by trooper name for consistent UI display.
 *
 * @method static Collection<int, Trooper> call(Trooper $trooper, TrooperFilter $filter, array $data)
 */
final class GetTroopersForPicker extends Message
{
    /**
     * Create a new query instance.
     *
     * @param  Trooper  $trooper  The trooper requesting troopers
     * @param  TrooperFilter  $filter  Search and role filtering criteria
     * @param  array  $data  Query parameters:
     *                       - 'moderated_only' (bool): Filter to moderated organizations only (default: false)
     *                       - 'organization_id' (int|null): Filter to specific org and descendants (default: null)
     *                       - 'picker_mode' (string|null): Optional mode to adjust filtering logic (default: null)
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly TrooperFilter $filter,
        array $data)
    {
        $this->moderated_only = filter_var($data['moderated_only'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $this->organization_id = empty($data['organization_id']) ? null : intval($data['organization_id']);
        $this->picker_mode = isset($data['picker_mode']) ? TrooperSearchMode::from($data['picker_mode']) : null;
    }

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
     * @return Collection<int, Trooper> Collection of filtered troopers, or empty if no filter applied
     */
    public function handle(): Collection
    {
        $query = $this->buildBaseQuery();
        $has_filter = $this->filter->hasFilter();
        $is_friends_mode = $this->picker_mode == TrooperSearchMode::FRIENDS;

        if ($is_friends_mode && !$has_filter)
        {
            $query = $this->scopeToFriends($query, $this->trooper);
        }

        if ($has_filter)
        {
            $query = $query->filterWith($this->filter);
        }

        if (!$is_friends_mode && !$has_filter)
        {
            return collect([]);
        }

        $order_term = $this->filter->searchTermValue();
        $results = $this->resolveOrder($query, $order_term)->get();

        if ($results->isEmpty() && $has_filter && $this->filter->hasMultiWordSearchTerm())
        {
            $query = $this->buildBaseQuery()->filterWith($this->filter->useLooseSearch());
            $results = $this->resolveOrder($query, $order_term)->get();
        }

        return $results;
    }

    /**
     * Whether to filter to only organizations moderated by the trooper.
     */
    public readonly bool $moderated_only;

    /**
     * Optional organization ID to filter by (includes descendants via node_path).
     */
    public readonly ?int $organization_id;

    /**
     * Optional picker mode to adjust filtering logic.
     */
    public readonly ?TrooperSearchMode $picker_mode;

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
     * @return Builder<Trooper>
     */
    private function buildBaseQuery(): Builder
    {
        $query = Trooper::active()
            ->whereNotNull(Trooper::SETUP_COMPLETED_AT)
            ->where(function ($q)
            {
                $q->whereNull(Trooper::GUARDIAN_ID)
                    ->orWhere(Trooper::GUARDIAN_ID, $this->trooper->id);
            })
            ->with('organizations');

        if ($this->organization_id)
        {
            $query = $query->whereHas('organizations', function ($q)
            {
                $q->where('tt_organizations.id', $this->organization_id);
            });
        }

        if ($this->moderated_only)
        {
            $query = $query->moderatedBy($this->trooper);
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
