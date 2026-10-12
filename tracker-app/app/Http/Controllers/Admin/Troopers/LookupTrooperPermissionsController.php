<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\Permissions\LookupTrooperPermissionsPageData;
use App\Models\Trooper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Returns a trooper's current role and forum groups for the permission request form.
 */
class LookupTrooperPermissionsController extends Controller
{
    public function __invoke(Request $request, Trooper $trooper): JsonResponse
    {
        $data = LookupTrooperPermissionsPageData::call($request);

        return response()->json($data);
    }
}
