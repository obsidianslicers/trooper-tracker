<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Enums\FlashType;
use App\Http\Requests\Admin\Troopers\MarkUntrustedRequest;
use App\Messages\Troopers\Commands\MarkTrooperUntrusted;
use App\Models\Trooper;
use Inertia\Response as InertiaResponse;

class MarkTrooperUntrustedController extends ProfileCommandController
{
    public function __invoke(MarkUntrustedRequest $request, Trooper $trooper): InertiaResponse
    {
        MarkTrooperUntrusted::call(
            trooper: $trooper,
            reason: $request->validated('untrusted_reason'),
            review_costumes: $request->boolean('review_costumes'),
        );

        FlashType::success("{$trooper->display_name} is marked do not trust.");

        return $this->respondWithResults($request);
    }
}
