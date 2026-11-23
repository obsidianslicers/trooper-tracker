<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Troopers\Commands\UpdateTrooperGuardianRequest;
use App\Messages\Troopers\Commands\UpdateTrooperGuardian;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

class UpdateTrooperGuardianController extends ProfileCommandController
{
    public function __invoke(UpdateTrooperGuardianRequest $request, Trooper $trooper): InertiaResponse
    {
        UpdateTrooperGuardian::call(trooper: $trooper, valid_data: $request->validated());

        FlashType::updated($trooper);

        return $this->respondWithResults($request);
    }
}
