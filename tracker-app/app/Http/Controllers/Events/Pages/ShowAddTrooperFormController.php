<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Messages\Events\PageData\AddTrooperFormPageData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Add a Friend, step 2: once a trooper is picked, what to choose to sign them up in one go,
 * like the trooper's own Sign Up: their costume (preselected when we can guess it) and,
 * when the shift needs them, their organization and station.
 */
class ShowAddTrooperFormController
{
    public function __invoke(Request $request): JsonResponse
    {
        return response()->json(AddTrooperFormPageData::call($request));
    }
}
