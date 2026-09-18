<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\ServiceRecords;

use App\Bus\MagicBus;
use App\Enums\FlashType;
use App\Features\Events\Commands\AssignEventTrooperCreditCommand;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRecords\AssignCreditRequest;
use App\Models\EventTrooper;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AssignCreditController extends Controller
{
    public function __invoke(AssignCreditRequest $request, EventTrooper $event_trooper, MagicBus $bus): InertiaResponse
    {
        $bus->send(new AssignEventTrooperCreditCommand(
            event_trooper: $event_trooper,
            organization_ids: $request->validated('organization_ids'),
            actor: $request->user(),
            is_override: $request->boolean('is_override'),
        ));

        FlashType::success('Credit assigned.');

        // Render the page directly (no redirect) so the frontend's `only: ['flash']` partial
        // reload is honored in a single round trip — the rows/filtered_trooper props are already
        // updated optimistically client-side, so there's nothing else to send back.
        return Inertia::render('admin/service-records/MissingCredits');
    }
}
