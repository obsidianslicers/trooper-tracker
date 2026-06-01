<?php

declare(strict_types=1);

namespace App\Http\Controllers\ServiceRecords;

use App\Features\Reports\Queries\GetCostumeArsenalQuery;
use App\Http\Controllers\MagicBusController;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CostumesController extends MagicBusController
{
    public function __invoke(Request $request): View
    {
        $days = $this->resolveDays($request);

        $costumes = $this->bus->send(new GetCostumeArsenalQuery($days));

        return view('pages.service-records.costumes', compact('costumes', 'days'));
    }

    private function resolveDays(Request $request): ?int
    {
        if (!$request->has('days') || $request->query('days') === 'all')
        {
            return null;
        }

        $days = $request->integer('days');

        return in_array($days, [30, 60, 90, 180, 360], true) ? $days : null;
    }
}
