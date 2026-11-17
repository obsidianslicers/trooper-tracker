<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events;

use App\Http\Controllers\Controller;
use App\Messages\Events\PageData\AdminEventPageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Base for the Command Staff event page's postbacks.
 *
 * Answers with the refreshed page data as `results` so the Svelte page reacts to it
 * without a redirect.
 */
abstract class EventCommandController extends Controller
{
    protected function respondWithResults(Request $request): InertiaResponse
    {
        return Inertia::render('admin/events/Update', [
            'results' => AdminEventPageData::call($request),
        ]);
    }
}
