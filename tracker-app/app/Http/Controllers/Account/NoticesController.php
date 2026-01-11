<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Displays the notices/announcements page for the authenticated trooper.
 *
 * This controller follows the Action-Domain-Responder (ADR) pattern:
 * - **Action (Controller):** Retrieves authenticated trooper from request
 * - **Domain (Models):** Uses Notice::visibleTo() scope to filter organization-specific notices
 * - **Responder:** Renders notices list view with unread notices highlighted
 *
 * The visibleTo() scope ensures troopers only see:
 * 1. Global notices (no organization assigned)
 * 2. Notices assigned to their organization or parent organizations
 * 3. Optionally filtered to only unread notices (unread_only = true)
 *
 * Notices are ordered by start date to show most relevant first.
 */
class NoticesController extends Controller
{
    /**
     * Display the notices page for the authenticated trooper.
     *
     * Retrieves all unread notices visible to the trooper, ordered by start date.
     * The visibleTo(trooper, true) scope filters for:
     * - Notices in the trooper's organization hierarchy
     * - Global notices (no organization)
     * - Only unread notices (is_read = false or no read record)
     *
     * @param Request $request The incoming HTTP request containing authenticated trooper.
     * @return View The notices page with filtered notice collection.
     */
    public function __invoke(Request $request): View
    {
        $trooper = $request->user();

        $notices = Notice::visibleTo($trooper, true)
            ->orderBy(Notice::STARTS_AT)
            ->get();

        $data = compact('notices');

        return view('pages.account.notices', $data);
    }
}
