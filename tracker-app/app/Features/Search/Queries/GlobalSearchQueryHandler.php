<?php

declare(strict_types=1);

namespace App\Features\Search\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Event;
use App\Models\Trooper;
use Illuminate\Support\Facades\DB;

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
            $troopers = Trooper::query()
                ->where(function ($q) use ($like) {
                    $q->where(Trooper::DISPLAY_NAME, 'like', $like)
                        ->orWhere(Trooper::EMAIL, 'like', $like)
                        ->orWhereExists(function ($q) use ($like) {
                            $q->select(DB::raw(1))
                                ->from('tt_trooper_organizations')
                                ->whereColumn('tt_trooper_organizations.trooper_id', 'tt_troopers.id')
                                ->whereNull('tt_trooper_organizations.deleted_at')
                                ->where('tt_trooper_organizations.identifier', 'like', $like);
                        });
                })
                ->with('organizations')
                ->orderBy(Trooper::DISPLAY_NAME)
                ->limit(25)
                ->get();
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
}
