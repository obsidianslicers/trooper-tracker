<?php

declare(strict_types=1);

namespace App\Http\Controllers\Widgets;

use App\Http\Controllers\PageController;
use App\Messages\Support\Queries\GetSupportGoal;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SupportDisplayHtmxController extends PageController
{
    public function __invoke(Request $request): View
    {
        $support = GetSupportGoal::call();

        $data = [
            'goal' => $support['goal'],
            'current' => $support['current'],
            'progress' => number_format($support['progress'], 0),
            'message' => $support['message'],
        ];

        return view('widgets.support', $data);
    }
}
