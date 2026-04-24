<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Enums\SearchType;
use App\Enums\TrooperTheme;
use App\Features\Search\Queries\GlobalSearchQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $term = (string) $request->query('q', '');
        $type = SearchType::tryFrom((string) $request->query('type', SearchType::ALL->value)) ?? SearchType::ALL;

        $results = null;
        $show_troopers = in_array($type, [SearchType::ALL, SearchType::TROOPERS], true);
        $show_events = in_array($type, [SearchType::ALL, SearchType::EVENTS], true);

        if (strlen(trim($term)) >= 2)
        {
            $results = $this->bus->send(new GlobalSearchQuery($term, $type->value));
        }

        $search_title = match ($request->user()->theme)
        {
            TrooperTheme::STORMTROOPER => 'Holonet Scanner',
            TrooperTheme::BOUNTY_HUNTER => 'Bounty Puck',
            TrooperTheme::SITH => 'Force Sight',
            TrooperTheme::CLONE => 'Battalion Ledger',
            TrooperTheme::REBEL => 'Echo Base Intel',
            default => 'Search Results',
        };

        $search_types = SearchType::toArray();
        $type = $type->value;

        return view('pages.search.results', compact('term', 'type', 'results', 'search_title', 'search_types', 'show_troopers', 'show_events'));
    }
}
