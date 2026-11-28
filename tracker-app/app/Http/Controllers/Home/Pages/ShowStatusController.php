<?php

declare(strict_types=1);

namespace App\Http\Controllers\Home\Pages;

use App\Messages\System\PageData\StatusPageData;
use Illuminate\Http\JsonResponse;

class ShowStatusController
{
    public function __invoke(): JsonResponse
    {
        return response()->json(StatusPageData::call())->header('Cache-Control', 'no-store');
    }
}
