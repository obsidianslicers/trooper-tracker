<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\AwardsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays awarded trooper activity.
 */
class ShowAwardsController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = AwardsPageData::call($request);

        return Inertia::render('service-records/Awards', $data);
    }
}
