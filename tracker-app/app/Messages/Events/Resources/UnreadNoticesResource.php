<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Messages\Notices\Resources\UnreadNoticeResource;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The unread notices above the events list: how many there are, and the notice itself when
 * there is only one.
 *
 * @property int $resource The number of unread notices
 */
class UnreadNoticesResource extends JsonResource
{
    public function __construct(int $count, private readonly ?Notice $notice)
    {
        parent::__construct($count);
    }

    /**
     * @return array{count: int, notice: UnreadNoticeResource|null}
     */
    public function toArray(Request $request): array
    {
        return [
            'count' => $this->resource,
            'notice' => $this->notice ? new UnreadNoticeResource($this->notice) : null,
        ];
    }
}
