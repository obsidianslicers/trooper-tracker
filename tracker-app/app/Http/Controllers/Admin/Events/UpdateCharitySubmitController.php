<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Enums\FlashType;
use App\Http\Controllers\PageController;
use App\Http\Requests\Admin\Events\UpdateCharityRequest;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

class UpdateCharitySubmitController extends PageController
{
    public function __invoke(UpdateCharityRequest $request, Event $event): RedirectResponse
    {
        $this->authorize('update', $event);

        foreach ($request->validated('shifts', []) as $id => $input)
        {
            $shift = $event->event_shifts->firstWhere('id', (int) $id);

            if ($shift === null)
            {
                continue;
            }

            $shift->charity_name = $input['charity_name'] ?? null;
            $shift->charity_hours = filled($input['charity_hours'] ?? null) ? (int) $input['charity_hours'] : null;
            $shift->charity_direct_funds = (float) ($input['charity_direct_funds'] ?? 0);
            $shift->charity_indirect_funds = (float) ($input['charity_indirect_funds'] ?? 0);
            $shift->charity_notes = $input['charity_notes'] ?? null;

            $shift->save();
        }

        FlashType::updated($event);

        return redirect()->route('admin.events.charity', compact('event'));
    }
}
