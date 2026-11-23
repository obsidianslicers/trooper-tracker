<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\AwardsPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays awarded trooper activity.
 */
class ShowAwardsController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = AwardsPageData::call($request);

        return view('pages.service-records.awards', $data);
    }
}
