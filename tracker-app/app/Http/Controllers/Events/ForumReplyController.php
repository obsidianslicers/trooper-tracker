<?php

declare(strict_types=1);

namespace App\Http\Controllers\Events;

use App\Facades\TroopTrackerFacade;
use App\Jobs\SendForumPostCommandStaffNotificationsJob;
use App\Models\Event;
use App\Services\Forums\XenforoService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ForumReplyController
{
    public function __invoke(Request $request, Event $event, XenforoService $xenforo): Response
    {
        if (! TroopTrackerFacade::isXenforoIntegrationConfigured() || empty($event->thread_id))
        {
            abort(422);
        }

        $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'notify_command_staff' => ['nullable', 'boolean'],
        ]);

        $user_id = $xenforo->resolve_user_id_for_trooper($request->user()->id);

        $result = $xenforo->create_post(
            thread_id: (int) $event->thread_id,
            message: $request->input('message'),
            user_id: $user_id
        );

        $status = $result['status'] ?? 0;
        $success = $status >= 200 && $status < 300;

        if ($success && $request->boolean('notify_command_staff'))
        {
            SendForumPostCommandStaffNotificationsJob::dispatch($event, $request->user());
        }

        $smilies = $xenforo->get_smilies();

        $xenforoThreadPosts = $xenforo->get_thread_posts(
            (int) $event->thread_id,
            exclude_post_id: (int) $event->post_id,
            per_page: 50,
            max_pages: 20
        );

        $xenforoBaseUrl = rtrim(config('services.xenforo.base_url', env('XENFORO_BASE_URL', '')), '/');

        $flashMessage = json_encode([
            'message' => $success ? 'Reply posted!' : 'Failed to post reply.',
            'type' => $success ? 'success' : 'danger',
        ]);

        return response()
            ->view('pages.events.inc.xenforo-comments', compact('event', 'xenforoBaseUrl', 'xenforoThreadPosts', 'smilies'))
            ->header('X-Flash-Message', $flashMessage);
    }
}
