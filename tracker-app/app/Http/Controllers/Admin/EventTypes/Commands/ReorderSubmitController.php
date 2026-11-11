<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Commands;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EventTypes\ReorderRequest;
use App\Messages\EventTypes\Commands\ReorderEventTypes;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ReorderSubmitController extends Controller
{
    public function __invoke(ReorderRequest $request): SymfonyResponse
    {
        ReorderEventTypes::call(ordered_ids: $request->validated('ids'));

        return redirect()->back();
    }
}
