<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Messages\Troopers\Commands\UnmarkTrooperRip;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class UnmarkTrooperRipController extends ProfileCommandController
{
    public function __invoke(Request $request, Trooper $trooper): InertiaResponse
    {
        $this->authorize('unmarkRip', $trooper);

        UnmarkTrooperRip::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} has been restored to pending status.");

        return $this->respondWithResults($request);
    }
}
