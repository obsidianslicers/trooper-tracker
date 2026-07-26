<?php

declare(strict_types=1);

namespace App\Features\Search\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Support\Collection;

/**
 * @implements QueryHandlerInterface<GlobalSearchQuery>
 */
readonly class GlobalSearchQueryHandler implements QueryHandlerInterface
{
    public function __invoke(object $message): mixed
    {
        $term = trim($message->term);
        $like = '%'.$term.'%';

        $troopers = collect();
        $events = collect();

        if (strlen($term) < 2)
        {
            return compact('troopers', 'events');
        }

        if (in_array($message->type, ['all', 'troopers']))
        {
            $troopers = $this->findTroopers($term, loose: false);

            if ($troopers->isEmpty() && str_contains($term, ' '))
            {
                $troopers = $this->findTroopers($term, loose: true);
            }
        }

        if (in_array($message->type, ['all', 'events']))
        {
            $events = Event::query()
                ->with('organization:id,name')
                ->where(Event::NAME, 'like', $like)
                ->orderByDesc(Event::EVENT_START)
                ->limit(25)
                ->get();
        }

        return compact('troopers', 'events');
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
