<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Notice;
use App\Models\TrooperNotice;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the submission of the notification settings form via an HTMX request.
 */
class NoticesSubmitHtmxController extends Controller
{
    /**
     * Handle the incoming request to update notification settings.
     *
     * @param Request $request The incoming HTTP request.
     * @return Response|View The rendered notification settings view with a flash message header.
     */
    public function __invoke(Request $request, Notice $notice): Response|View
    {
        $trooper = $request->user();

        TrooperNotice::firstOrCreate(
            [
                TrooperNotice::TROOPER_ID => $trooper->id,
                TrooperNotice::NOTICE_ID => $notice->id,
            ],
            [
                TrooperNotice::IS_READ => true,
            ]
        );

        return response('<button class="btn btn-outline-secondary btn-sm float-end"><i class="fa fa-envelope-open-text"></i></button>');
    }
}