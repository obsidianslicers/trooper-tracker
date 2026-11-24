<?php

declare(strict_types=1);

namespace App\Messages\ServiceRecords\Resources;

use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Someone on Command Staff and the clubs they moderate (service-records/CommandStaff).
 *
 * @mixin Trooper
 */
class CommandStaffMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $organizations = $this->resource->cs_organizations ?? collect();

        return [
            Trooper::ID => $this->resource->id,
            Trooper::DISPLAY_NAME => $this->resource->display_name,
            'url' => route('service-records.trooper', ['trooper' => $this->resource]),
            'role' => $this->resource->membership_role->label(),
            'organizations' => $organizations->pluck(Organization::NAME)->implode(', '),
        ];
    }
}
