<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Xenforo\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use TroopTracker\Plugins\Xenforo\Jobs\SendForumPostCommandStaffNotificationsJob;
use TroopTracker\Plugins\Xenforo\Models\EventThread;
use TroopTracker\Plugins\Xenforo\Services\XenforoService;

class ForumReplyController
{
    public function __invoke(Request $request, Event $event, XenforoService $xenforo): Response
    {
        $thread = EventThread::linked($event->id);

        if (!XenforoService::isConfigured() || $thread === null)
        {
            abort(422);
        }

        $request->validate([
            'message' => ['required', 'string', 'max:10000'],
            'notify_command_staff' => ['nullable', 'boolean'],
        ]);

        $user_id = $xenforo->resolve_user_id_for_trooper($request->user()->id);

        $result = $xenforo->create_post(
            thread_id: (int) $thread->thread_id,
            message: $request->input('message'),
            user_id: $user_id
        );

        $status = $result['status'] ?? 0;
        $success = $status >= 200 && $status < 300;

        if ($success && $request->boolean('notify_command_staff'))
        {
            SendForumPostCommandStaffNotificationsJob::dispatch($event, $request->user());
        }

        $flashMessage = json_encode([
            'message' => $success ? 'Reply posted!' : 'Failed to post reply.',
            'type' => $success ? 'success' : 'danger',
        ]);

        return response()
            ->view('xenforo::events.comments', compact('event'))
            ->header('X-Flash-Message', $flashMessage);
    }
}
