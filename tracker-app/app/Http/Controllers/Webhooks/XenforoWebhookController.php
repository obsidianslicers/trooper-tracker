<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Jobs\SendEventForumPostNotificationsJob;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class XenforoWebhookController
{
    public function __invoke(Request $request): Response
    {
        if (!$this->signatureValid($request))
        {
            return response('', 401);
        }

        $payload = $request->json()->all();

        if (($payload['event'] ?? '') !== 'insert')
        {
            return response('', 204);
        }

        $data = $payload['data'] ?? [];
        $thread_id = (int) ($data['thread_id'] ?? 0);

        if ($thread_id <= 0)
        {
            return response('', 204);
        }

        $event = Event::where('thread_id', $thread_id)->first();

        if ($event === null)
        {
            return response('', 204);
        }

        $post_id = (int) ($data['post_id'] ?? 0);
        $username = (string) ($data['username'] ?? 'Unknown');
        $xenforo_user_id = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $message = (string) ($data['message'] ?? '');

        SendEventForumPostNotificationsJob::dispatch($event, $post_id, $username, $xenforo_user_id, $message);

        return response('', 204);
    }

    private function signatureValid(Request $request): bool
    {
        $secret = config('services.xenforo.webhook_secret');

        if (empty($secret))
        {
            Log::warning('XenForo webhook received but XENFORO_WEBHOOK_SECRET is not configured.');

            return false;
        }

        $header = (string) $request->header('xf-webhook-secret', '');

        return hash_equals($secret, $header);
    }
}
