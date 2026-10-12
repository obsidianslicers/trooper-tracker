<?php

declare(strict_types=1);

namespace App\Messages\Notices\Commands;

use App\Models\Notice;
use App\Models\NoticeTrooper;
use App\Models\Trooper;
use Hyperdrive\Message;

/**
 * Handles the marking of a notice as read for a trooper.
 * This command is responsible for updating the read status of a notice for the given trooper.
 *
 * @method static void call(Trooper $trooper, Notice $notice)
 */
final class UpdateNoticeAsRead extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly Notice $notice
    ) {}

    public function handle(): void
    {
        $notice_trooper = NoticeTrooper::query()
            ->where(NoticeTrooper::TROOPER_ID, $this->trooper->id)
            ->where(NoticeTrooper::NOTICE_ID, $this->notice->id)
            ->first();

        if ($notice_trooper == null)
        {
            $notice_trooper = new NoticeTrooper;

            $notice_trooper->trooper_id = $this->trooper->id;
            $notice_trooper->notice_id = $this->notice->id;
        }

        $notice_trooper->is_read = true;
        $notice_trooper->save();
    }
}
