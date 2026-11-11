<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Messages\Troopers\Commands\MarkTrooperRip;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class MarkTrooperRipController extends ProfileCommandController
{
    public function __invoke(Request $request, Trooper $trooper): InertiaResponse
    {
        $this->authorize('markRip', $trooper);

        MarkTrooperRip::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} has been marked R.I.P. — In Memoriam.");

        return $this->respondWithResults($request);
    }
}
