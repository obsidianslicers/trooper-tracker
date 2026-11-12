<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Troopers\Commands;

use App\Http\Controllers\Controller;
use App\Messages\Troopers\PageData\AdminProfilePageData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

/**
 * Base for trooper profile postbacks.
 *
 * Answers with the refreshed profile page data as `results` (like the account
 * commands) so the Svelte page reacts to it without a redirect.
 */
abstract class ProfileCommandController extends Controller
{
    protected function respondWithResults(Request $request): InertiaResponse
    {
        return Inertia::render('admin/troopers/Profile', [
            'results' => AdminProfilePageData::call($request),
        ]);
    }
}
