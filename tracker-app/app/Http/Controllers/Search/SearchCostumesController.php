<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Messages\Costumes\PageData\SearchCostumesPageData;
use App\Models\Trooper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SearchCostumesController extends Controller
{
    public function __invoke(Request $request, ?Trooper $trooper = null): InertiaResponse|SymfonyResponse|JsonResponse
    {
        $data = SearchCostumesPageData::call($request);

        return response()->json($data);
    }
}
