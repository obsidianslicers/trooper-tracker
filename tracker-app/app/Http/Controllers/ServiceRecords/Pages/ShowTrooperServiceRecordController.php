<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords\Pages;

use App\Http\Controllers\PageController;
use App\Messages\ServiceRecords\PageData\TrooperServiceRecordPageData;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Displays a trooper's service record dashboard.
 */
class ShowTrooperServiceRecordController extends PageController
{
    public function __invoke(Request $request, Trooper $trooper): InertiaResponse
    {
        if ($trooper->id == $request->user()->id)
        {
            $this->crumbs->addRoute(__('account.Profile'), 'account.index');
        }

        $data = TrooperServiceRecordPageData::call($request);

        return Inertia::render('service-records/Trooper', $data);
    }
}
