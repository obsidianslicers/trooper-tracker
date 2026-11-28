<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\Corrections\TroopCorrectionPageData;
use App\Models\TroopCorrection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * One request for troop credit, where the event coordinator confirms or denies it.
 */
class ShowTroopCorrectionController extends PageController
{
    protected function initialized(): void
    {
        $this->crumbs->addRoute(__('events.Events'), 'events.list');
    }

    public function __invoke(Request $request, TroopCorrection $troop_correction): InertiaResponse
    {
        abort_unless($troop_correction->isVisibleTo($request->user()), 403);

        $data = TroopCorrectionPageData::call($request);

        return Inertia::render('events/TroopCorrection', $data);
    }
}
