<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Pages;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\RedirectResponse;

/**
 * Keeps the old address of each event tab (shifts, charity, roster, uploads, mission
 * review) working: opens the event page on that tab.
 */
class EditEventTabController extends Controller
{
    public function __invoke(Event $event, string $tab): RedirectResponse
    {
        $this->authorize('update', $event);

        return redirect()->route('admin.events.edit', ['event' => $event, 'tab' => $tab]);
    }
}
