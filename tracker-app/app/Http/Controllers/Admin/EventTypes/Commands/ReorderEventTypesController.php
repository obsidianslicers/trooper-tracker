<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Commands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventTypes\Commands\ReorderEventTypesRequest;
use App\Messages\EventTypes\Commands\ReorderEventTypes;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReorderEventTypesController extends Controller
{
    public function __invoke(ReorderEventTypesRequest $request): SymfonyResponse
    {
        ReorderEventTypes::call(ordered_ids: $request->validated('ids'));

        return redirect()->back();
    }
}
