<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\EventTypes\Commands;

use App\Enums\FlashType;
use App\Http\Controllers\Controller;
use App\Messages\EventTypes\Commands\DeleteEventType;
use App\Models\EventType;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class DeleteSubmitController extends Controller
{
    public function __invoke(Request $request, EventType $event_type): SymfonyResponse
    {
        $this->authorize('update', $event_type);

        if ($request->user()->cannot('delete', $event_type))
        {
            FlashType::danger("\"{$event_type->name}\" is used by past events, so it can't be deleted. "
                .'Uncheck "Offered on the event form" to hide it instead.');

            return redirect()->back();
        }

        DeleteEventType::call(event_type: $event_type);

        FlashType::deleted($event_type);

        return redirect()->route('admin.event-types.index');
    }
}
