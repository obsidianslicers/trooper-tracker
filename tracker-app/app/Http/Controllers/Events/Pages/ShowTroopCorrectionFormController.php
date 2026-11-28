<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\Corrections\TroopCorrectionFormPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * The Request Troop Credit page: find a completed event by date or title, then ask for
 * credit for it.
 */
class ShowTroopCorrectionFormController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('events.Events'), 'events.list');
    }

    public function __invoke(Request $request): InertiaResponse
    {
        $data = TroopCorrectionFormPageData::call($request);

        return Inertia::render('events/RequestCredit', $data);
    }
}
