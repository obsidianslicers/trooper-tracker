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
        $thread_id = (int) ($payload['thread']['thread_id'] ?? 0);

        if ($thread_id <= 0)
        {
            return response('', 204);
        }

        $event = Event::where('thread_id', $thread_id)->first();

        if ($event === null)
        {
            return response('', 204);
        }

        $post = $payload['post'] ?? [];
        $post_id = (int) ($post['post_id'] ?? 0);
        $username = (string) ($post['User']['username'] ?? $post['username'] ?? 'Unknown');
        $xenforo_user_id = isset($post['User']['user_id']) ? (int) $post['User']['user_id'] : null;
        $message = (string) ($post['message'] ?? '');

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

        $header = $request->header('X-XF-Signature', '');
        if (!is_string($header) || !str_starts_with($header, 'sha256='))
        {
            return false;
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $secret);

        return hash_equals($expected, $header);
    }
}
