<?php

declare(strict_types=1);

namespace App\Messages\Notices\Commands;

use App\Enums\NoticeType;
use App\Models\Notice;
use Illuminate\Support\Carbon;
use Hyperdrive\Message;

/**
 * @method static Notice call(int|null $organization_id, string $title, NoticeType $type, Carbon $starts_at, Carbon $ends_at, string $message)
 */
final class CreateNotice extends Message
{
    public function __construct(
        private readonly ?int $organization_id,
        private readonly string $title,
        private readonly NoticeType $type,
        private readonly Carbon $starts_at,
        private readonly Carbon $ends_at,
        private readonly string $message,
    ) {}

    public function handle(): Notice
    {
        $notice = new Notice;

        $notice->organization_id = $this->organization_id;
        $notice->title = $this->title;
        $notice->type = $this->type;
        $notice->starts_at = $this->starts_at;
        $notice->ends_at = $this->ends_at;
        $notice->message = $this->message;

        $notice->save();

        return $notice;
    }
}
