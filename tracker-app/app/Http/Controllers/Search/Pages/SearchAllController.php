<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Search\PageData\SearchAllPageData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchAllController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = SearchAllPageData::call($request);

        return view('pages.search.results', $data);
    }
}
