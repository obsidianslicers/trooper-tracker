<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

/**
 * Keeps the old address of each event tab (shifts, charity, roster, uploads, mission
 * review) working: opens the event page on that tab.
 */
class OpenTabController extends Controller
{
    public function __invoke(Event $event, string $tab): RedirectResponse
    {
        $this->authorize('update', $event);

        return redirect()->route('admin.events.update', ['event' => $event, 'tab' => $tab]);
    }
}
