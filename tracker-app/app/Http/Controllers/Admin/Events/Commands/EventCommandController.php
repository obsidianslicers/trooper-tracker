<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Commands;

use App\Http\Controllers\Controller;
use App\Messages\Events\PageData\AdminEventPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Base for the Command Staff event page's postbacks.
 *
 * Answers with the refreshed sections a save changed as `results`, so the Svelte page
 * updates just those, without a redirect.
 */
abstract class EventCommandController extends Controller
{
    /**
     * @param  string  ...$sections  AdminEventPageData sections to send; none sends them all
     */
    protected function respondWithResults(Request $request, string ...$sections): InertiaResponse
    {
        return Inertia::render('admin/events/Update', [
            'results' => AdminEventPageData::call($request, ['sections' => $sections ?: null]),
        ]);
    }
}
