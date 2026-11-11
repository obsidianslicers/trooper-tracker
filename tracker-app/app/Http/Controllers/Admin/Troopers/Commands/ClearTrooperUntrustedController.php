<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Messages\Troopers\Commands\ClearTrooperUntrusted;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class ClearTrooperUntrustedController extends ProfileCommandController
{
    public function __invoke(Request $request, Trooper $trooper): InertiaResponse
    {
        $this->authorize('markUntrusted', $trooper);

        ClearTrooperUntrusted::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} is no longer marked do not trust.");

        return $this->respondWithResults($request);
    }
}
