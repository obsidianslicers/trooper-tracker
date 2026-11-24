<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\CostumeLeaderboardPageData;
use App\Models\Costume;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ShowCostumeLeaderboardController extends PageController
{
    public function __invoke(Request $request, Costume $costume): InertiaResponse
    {
        $data = CostumeLeaderboardPageData::call($request);

        return Inertia::render('service-records/Costume', $data);
    }
}
