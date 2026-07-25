<?php

declare(strict_types=1);

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\SearchTroopersPageData;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class SearchTroopersController extends Controller
{
    public function __invoke(Request $request): InertiaResponse|SymfonyResponse|JsonResponse
    {
        $data = SearchTroopersPageData::call($request);

        return response()->json($data);
    }
}
