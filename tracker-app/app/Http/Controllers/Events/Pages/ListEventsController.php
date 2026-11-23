<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\ListEventsPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The upcoming events list, where troopers land after signing in.
 */
class ListEventsController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = ListEventsPageData::call($request);

        if ($data['support'] !== null)
        {
            $data['support'] = Inertia::defer($data['support']);
        }

        return Inertia::render('events/List', $data);
    }
}
