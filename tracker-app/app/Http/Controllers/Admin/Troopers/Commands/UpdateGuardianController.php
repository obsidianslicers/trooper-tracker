<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Troopers\UpdateGuardianRequest;
use App\Messages\Troopers\Commands\UpdateTrooper;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

class UpdateGuardianController extends ProfileCommandController
{
    public function __invoke(UpdateGuardianRequest $request, Trooper $trooper): InertiaResponse
    {
        $data = $request->validated();

        if (array_key_exists('guardian_email', $data))
        {
            $guardian_email = $data['guardian_email'];
            $data[Trooper::GUARDIAN_ID] = $guardian_email
                ? Trooper::where(Trooper::EMAIL, $guardian_email)->value(Trooper::ID)
                : null;
            unset($data['guardian_email']);
        }

        UpdateTrooper::call(trooper: $trooper, valid_data: $data);

        FlashType::updated($trooper);

        return $this->respondWithResults($request);
    }
}
