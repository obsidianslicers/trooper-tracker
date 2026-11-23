<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\CostumeLeaderboardPageData;
use App\Models\Costume;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShowCostumeLeaderboardController extends PageController
{
    public function __invoke(Request $request, Costume $costume): View
    {
        $data = CostumeLeaderboardPageData::call($request);

        if ($request->isHtmx())
        {
            return view('pages.service-records.inc.costume-stats', $data);
        }

        return view('pages.service-records.costume', $data);
    }
}
