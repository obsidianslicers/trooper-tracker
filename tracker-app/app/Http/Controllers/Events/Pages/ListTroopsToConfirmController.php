<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events\Pages;

use App\Http\Controllers\PageController;
use App\Messages\Events\PageData\ListTroopsToConfirmPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Every troop the trooper hasn't said whether they made it to. The bell shows the first few
 * and links here; so does the reminder.
 */
class ListTroopsToConfirmController extends PageController
{
    public function __invoke(Request $request): InertiaResponse
    {
        $data = ListTroopsToConfirmPageData::call(actor: $request->user());

        return Inertia::render('events/TroopsToConfirm', $data);
    }
}
