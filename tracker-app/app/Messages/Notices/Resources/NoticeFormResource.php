<?php

declare(strict_types=1);

namespace App\Messages\Notices\Resources;

use App\Messages\Troopers\Resources\TrooperStampsResource;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The fields of the notice create and update forms.
 *
 * @mixin Notice
 */
class NoticeFormResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Notice::ID => $this->id,
            Notice::ORGANIZATION_ID => $this->organization_id,
            'organization_name' => $this->organization?->name,
            Notice::TYPE => $this->type->value,
            Notice::TITLE => $this->title ?? '',
            Notice::STARTS_AT => $this->starts_at?->format('Y-m-d H:i'),
            Notice::ENDS_AT => $this->ends_at?->format('Y-m-d H:i'),
            Notice::MESSAGE => $this->message ?? '',
            TrooperStampsResource::TROOPER_STAMPS => $this->when(
                $this->exists,
                fn () => new TrooperStampsResource($this->resource)
            ),
        ];
    }
}
