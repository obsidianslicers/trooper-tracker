<?php

declare(strict_types=1);

namespace App\Messages\Notices\Resources;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * A notice shown on a trooper's landing page, with its message rendered from Markdown.
 *
 * @mixin Notice
 */
class UnreadNoticeResource extends JsonResource
{
    /**
     * @return array{id: int, type: string, heading: string, title: string, html: string}
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type->value,
            'heading' => $this->type->description(),
            'title' => $this->title,
            'html' => Str::markdown($this->message),
        ];
    }
}
