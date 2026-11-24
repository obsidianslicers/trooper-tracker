<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Search\PageData\SearchAllPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class SearchAllController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = SearchAllPageData::call($request);

        return Inertia::render('search/Results', $data);
    }
}
