<?php

declare(strict_types=1);

namespace App\Messages\Notices\Commands;

use App\Enums\NoticeType;
use App\Models\Notice;
use Hyperdrive\Message;
use Illuminate\Support\Carbon;

/**
 * Changes a notice's text, type and dates. Its organization never changes.
 *
 * @method static Notice call(Notice $notice, string $title, NoticeType $type, Carbon $starts_at, Carbon $ends_at, string $message)
 */
final class UpdateNotice extends Message
{
    public function __construct(
        private readonly Notice $notice,
        private readonly string $title,
        private readonly NoticeType $type,
        private readonly Carbon $starts_at,
        private readonly Carbon $ends_at,
        private readonly string $message,
    ) {}

    public function handle(): Notice
    {
        $this->notice->title = $this->title;
        $this->notice->type = $this->type;
        $this->notice->starts_at = $this->starts_at;
        $this->notice->ends_at = $this->ends_at;
        $this->notice->message = $this->message;

        $this->notice->save();

        return $this->notice;
    }
}
