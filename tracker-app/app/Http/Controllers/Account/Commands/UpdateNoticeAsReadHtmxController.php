<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account\Commands;

use App\Http\Controllers\Controller;
use App\Messages\Notices\Commands\UpdateNoticeAsRead;
use App\Models\Notice;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Marks a notice as read for the authenticated trooper via HTMX.
 *
 * This controller follows the Action-Domain-Responder (ADR) pattern:
 * - **Action (Controller):** Receives HTMX request with notice route parameter
 * - **Domain (Message):** Uses UpdateNoticeAsRead command to mark notice as read
 * - **Responder:** Returns HTML button fragment for HTMX swap
 *
 * When a trooper clicks the "Mark as Read" button in the notices list, this endpoint:
 * 1. Calls UpdateNoticeAsRead command to create/update NoticeTrooper pivot record
 * 2. Sets is_read flag to true
 * 3. Returns "Read" button HTML for HTMX to swap into the DOM
 *
 * This enables dynamic notice marking without full page reload.
 */
class UpdateNoticeAsReadHtmxController extends Controller
{
    /**
     * Mark a notice as read for the authenticated trooper via HTMX.
     *
     * Creates or updates a NoticeTrooper record marking the notice as read.
     * Returns an HTML button fragment that HTMX will swap into the page.
     *
     * @param  Request  $request  The incoming HTTP request containing authenticated trooper
     * @param  Notice  $notice  The notice to mark as read (route model binding)
     * @return Response The HTML button response for HTMX replacement
     */
    public function __invoke(Request $request, Notice $notice): Response
    {
        $trooper = $request->user();

        UpdateNoticeAsRead::call(
            trooper: $trooper,
            notice: $notice
        );

        return response('<button class="btn btn-outline-secondary btn-sm float-end"><i class="fa fa-envelope-open-text"></i></button>');
    }
}
