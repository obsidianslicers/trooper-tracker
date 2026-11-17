<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Enums\SearchType;
use App\Enums\TrooperTheme;
use App\Http\Controllers\PageController;
use App\Messages\Search\Queries\GlobalSearch;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends PageController
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
            $results = GlobalSearch::call(term: $term, type: $type->value);
        }

        $search_title = match ($request->user()->theme)
        {
            TrooperTheme::STORMTROOPER => __('search.Holonet Scanner'),
            TrooperTheme::BOUNTY_HUNTER => __('search.Bounty Puck'),
            TrooperTheme::SITH => __('search.Force Sight'),
            TrooperTheme::CLONE => __('search.Battalion Ledger'),
            TrooperTheme::REBEL => __('search.Echo Base Intel'),
            default => __('search.Search Results'),
        };

        $search_types = SearchType::toArray();
        $type = $type->value;

        return view('pages.search.results', compact('term', 'type', 'results', 'search_title', 'search_types', 'show_troopers', 'show_events'));
    }
}
