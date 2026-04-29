<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Facades\TroopTrackerFacade;
use App\Services\Forums\XenforoService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class DeleteEventForumThreadJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private readonly int $event_id,
        private readonly int $thread_id,
    ) {}

    public function handle(XenforoService $xenforo): void
    {
        if (! TroopTrackerFacade::isXenforoIntegrationConfigured())
        {
            return;
        }

        if ($this->thread_id <= 0)
        {
            return;
        }

        try
        {
            $result = $xenforo->delete_thread($this->thread_id);
            $status = (int) ($result['status'] ?? 0);

            if ($status === 404)
            {
                // Thread is already gone; treat as a successful end state.
                return;
            }

            if ($status < 200 || $status >= 300)
            {
                Log::warning('Failed to delete XenForo thread for deleted event', [
                    'event' => $this->event_id,
                    'thread_id' => $this->thread_id,
                    'status' => $status,
                    'body' => $result['body'] ?? null,
                ]);
            }
        }
        catch (Throwable $e)
        {
            Log::warning('Exception deleting XenForo thread for deleted event', [
                'event' => $this->event_id,
                'thread_id' => $this->thread_id,
                'error' => $e->getMessage(),
            ]);

            report($e);
        }
    }
}
