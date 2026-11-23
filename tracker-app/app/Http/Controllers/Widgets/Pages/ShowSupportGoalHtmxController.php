<?php

declare(strict_types=1);

namespace App\Http\Controllers\Widgets\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Support\PageData\SupportGoalWidgetPageData;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ShowSupportGoalHtmxController extends PageController
{
    public function __invoke(Request $request): View
    {
        $data = SupportGoalWidgetPageData::call($request);

        return view('widgets.support', $data);
    }
}
