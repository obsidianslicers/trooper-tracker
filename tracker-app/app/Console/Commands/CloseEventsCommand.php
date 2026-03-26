<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Bus\MagicBus;
use App\Enums\EventStatus;
use App\Features\Events\Queries\GetEventsToCloseQuery;
use App\Services\Forums\XenforoService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Artisan command to close events that have ended.
 *
 * This command orchestrates the process of identifying and closing events
 * whose end date has passed. It delegates the query logic to GetEventsToCloseQuery
 * service and updates each event's status to CLOSED.
 */
class CloseEventsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tracker:close-events';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close events that have ended';

    /**
     * Execute the console command.
     *
     * Orchestrates the event closing process by:
     * 1. Dispatching GetEventsToCloseQuery to retrieve active events that have ended
     * 2. Updating each event's status to CLOSED
     *
     * @param  MagicBus  $bus  The message bus for dispatching queries
     * @param  XenforoService  $xenforo  The XenForo API service for moving threads
     */
    public function handle(MagicBus $bus, XenforoService $xenforo): void
    {
        $events = $bus->send(new GetEventsToCloseQuery);

        foreach ($events as $event)
        {
            $event->status = EventStatus::CLOSED;
            $event->save();

            // If XenForo is configured and this event has a forum thread,
            // move it to the archive forum configured on the owning organization.
            if (! $event->create_forum_thread || empty($event->thread_id))
            {
                continue;
            }

            $organization = $event->organization ?? $event->primary_organization;

            if (! $organization || empty($organization->related_forum_archive))
            {
                continue;
            }

            $targetNodeId = (int) $organization->related_forum_archive;

            $result = $xenforo->move_thread((int) $event->thread_id, $targetNodeId);

            if (($result['status'] ?? 0) < 200 || ($result['status'] ?? 0) >= 300)
            {
                Log::warning('Failed to move XenForo thread to archive for event', [
                    'event' => $event->id,
                    'org' => $organization->id,
                    'thread_id' => $event->thread_id,
                    'target_node_id' => $targetNodeId,
                    'status' => $result['status'] ?? null,
                    'body' => $result['body'] ?? null,
                ]);
            }
        }
    }
}
