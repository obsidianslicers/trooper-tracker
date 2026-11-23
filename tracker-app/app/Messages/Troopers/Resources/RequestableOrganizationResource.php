<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * An organization a permission request can ask to moderate
 * (admin/troopers/components/permissions/OrganizationSelection): indented by its depth, and its
 * node path, which tells which organizations sit beneath it.
 *
 * @mixin Organization
 */
class RequestableOrganizationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Organization::ID => $this->id,
            Organization::NAME => $this->name,
            Organization::NODE_PATH => (string) $this->node_path,
            Organization::DEPTH => (int) $this->depth,
        ];
    }
}
