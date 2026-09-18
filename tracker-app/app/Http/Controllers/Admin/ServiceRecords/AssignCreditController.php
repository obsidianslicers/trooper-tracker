<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\ServiceRecords;

use App\Bus\MagicBus;
use App\Enums\FlashType;
use App\Features\Events\Commands\AssignEventTrooperCreditCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRecords\AssignCreditRequest;
use App\Models\EventTrooper;
use Illuminate\Http\RedirectResponse;

class AssignCreditController extends Controller
{
    public function __invoke(AssignCreditRequest $request, EventTrooper $event_trooper, MagicBus $bus): RedirectResponse
    {
        $bus->send(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: $request->validated('organization_ids'),
            actor: $request->user(),
            is_override: $request->boolean('is_override'),
        ));

        FlashType::success('Credit assigned.');

        return back();
    }
}
