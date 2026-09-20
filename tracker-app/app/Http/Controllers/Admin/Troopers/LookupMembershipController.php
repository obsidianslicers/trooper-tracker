<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\Membership\LookupMembershipPageData;
use App\Models\TrooperRequest;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Illuminate\Http\JsonResponse;

class LookupMembershipController extends Controller
{
    public function __invoke(Request $request, TrooperRequest $trooper_request): InertiaResponse|SymfonyResponse|JsonResponse
    {
        $data = LookupMembershipPageData::call($request);

        return response()->json($data);
    }
}
