<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Features\Search\Queries\GlobalSearchQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $term = (string) $request->query('q', '');
        $type = in_array($request->query('type'), ['all', 'troopers', 'events']) ? $request->query('type') : 'all';

        $results = null;

        if (strlen(trim($term)) >= 2) {
            $results = $this->bus->send(new GlobalSearchQuery($term, $type));
        }

        return view('pages.search.results', compact('term', 'type', 'results'));
    }
}
