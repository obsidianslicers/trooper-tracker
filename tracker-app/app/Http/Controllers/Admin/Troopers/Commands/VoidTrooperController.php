<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Messages\Troopers\Commands\VoidTrooper;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;

class VoidTrooperController extends ProfileCommandController
{
    public function __invoke(Request $request, Trooper $trooper): InertiaResponse
    {
        $this->authorize('void', $trooper);

        VoidTrooper::call(trooper: $trooper);

        FlashType::success("{$trooper->display_name} has been marked as created in error.");

        return $this->respondWithResults($request);
    }
}
