<?php

declare(strict_types=1);

namespace App\Messages\Notices\Resources;

use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff notices list.
 *
 * @mixin Notice
 */
class NoticeRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Notice::ID => $this->id,
            Notice::TITLE => $this->title,
            Notice::TYPE => $this->type->value,
            'organization' => $this->organization === null ? null : [
                'id' => $this->organization->id,
                'name' => $this->organization->name,
            ],
            'logo_url' => map_image_url(
                $this->organization->image_path_sm ?? '',
                'img/icons/organization-32x32.png'
            ),
            'can_update' => $this->canUpdate($request),
        ];
    }

    private function canUpdate(Request $request): bool
    {
        $trooper = $request->user();

        if ($this->organization_id === null)
        {
            return (bool) $trooper?->is_administrator;
        }

        return $trooper?->can('update', $this->resource) ?? false;
    }
}
