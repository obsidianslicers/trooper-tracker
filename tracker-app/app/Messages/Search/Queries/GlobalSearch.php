<?php

declare(strict_types=1);

namespace App\Messages\Search\Queries;

use App\Enums\SearchType;
use App\Models\Event;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static array call(string $term, string $type = SearchType::ALL->value)
 */
final class GlobalSearch extends Message
{
    public function __construct(
        private readonly string $term,
        private readonly string $type = SearchType::ALL->value,
    ) {}

    public function handle(): array
    {
        $term = trim($this->term);

        $troopers = collect();
        $events = collect();

        if (strlen($term) < 2)
        {
            return compact('troopers', 'events');
        }

        if (in_array($this->type, ['all', 'troopers']))
        {
            $troopers = $this->findMatchingTroopers($term);
        }

        if (in_array($this->type, ['all', 'events']))
        {
            $events = Event::query()
                ->with('organization:id,name')
                ->where(Event::NAME, 'like', '%'.$term.'%')
                ->orderByDesc(Event::EVENT_START)
                ->limit(25)
                ->get();
        }

        return compact('troopers', 'events');
    }

    /**
     * Find troopers matching the term, falling back to a loose (any-word) match if a
     * multi-word search comes back empty.
     *
     * @param  string  $term  The search term.
     * @return Collection<int, Trooper>
     */
    private function findMatchingTroopers(string $term): Collection
    {
        $troopers = $this->findTroopers($term, loose: false);

        if ($troopers->isEmpty() && str_contains($term, ' '))
        {
            $troopers = $this->findTroopers($term, loose: true);
        }

        return $troopers;
    }

    /**
     * Find troopers matching the given term.
     *
     * @param  string  $term  The search term.
     * @param  bool  $loose  When true, match troopers that contain any word of the term
     *                       rather than requiring every word (used as a fallback so a
     *                       multi-word search never dead-ends with zero results).
     * @return Collection<int, Trooper>
     */
    private function findTroopers(string $term, bool $loose): Collection
    {
        $query = $loose ? Trooper::query()->searchForAny($term) : Trooper::query()->searchFor($term);

        return $query
            ->with('organizations')
            ->orderByRelevance($term)
            ->limit(25)
            ->get();
    }
}
