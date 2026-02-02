<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\MagicBusController;
use App\Models\Notice;
use App\Models\NoticeTrooper;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Marks a notice as read for the authenticated trooper via HTMX.
 *
 * This controller follows the Action-Domain-Responder (ADR) pattern:
 * - **Action (Controller):** Receives HTMX request with notice route parameter
 * - **Domain (Models):** Uses NoticeTrooper::firstOrCreate() to mark notice as read
 * - **Responder:** Returns HTML button fragment for HTMX swap
 *
 * When a trooper clicks the "Mark as Read" button in the notices list, this endpoint:
 * 1. Creates or updates NoticeTrooper pivot record
 * 2. Sets is_read flag to true
 * 3. Returns "Read" button HTML for HTMX to swap into the DOM
 *
 * This enables dynamic notice marking without full page reload.
 */
class NoticesSubmitHtmxController extends MagicBusController
{
    /**
     * Mark a notice as read for the authenticated trooper.
     *
     * Creates or updates a NoticeTrooper record marking the notice as read.
     * Returns an HTML button fragment that HTMX will swap into the page.
     *
     * @param  Request  $request  The incoming HTTP request containing authenticated trooper.
     * @param  Notice  $notice  The notice to mark as read (route model binding).
     * @return Response The HTML button response for HTMX replacement.
     */
    public function __invoke(Request $request, Notice $notice): Response|View
    {
        $trooper = $request->user();

        $where = [
            NoticeTrooper::TROOPER_ID => $trooper->id,
            NoticeTrooper::NOTICE_ID => $notice->id,
        ];

        $set = [
            NoticeTrooper::IS_READ => true,
        ];

        NoticeTrooper::updateOrCreate($where, $set);

        return response('<button class="btn btn-outline-secondary btn-sm float-end"><i class="fa fa-envelope-open-text"></i></button>');
    }
}
