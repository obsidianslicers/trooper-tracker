<?php

declare(strict_types=1);

namespace App\Messages\Account\Resources;

use App\Enums\OrganizationType;
use App\Models\Organization;
use App\Models\TrooperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A club request from a denied application, shown on the denied page and filled back into its
 * resubmit form (account/Denied).
 *
 * @mixin TrooperRequest
 */
class DeniedRequestResource extends JsonResource
{
    /**
     * @param  ?Organization  $requested  the club, region or unit the trooper asked for
     */
    public function __construct(
        TrooperRequest $trooper_request,
        private readonly ?Organization $requested,
        private readonly ?string $path,
    ) {
        parent::__construct($trooper_request);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $requested = $this->requested;

        return [
            TrooperRequest::ID => $this->resource->id,
            'club' => $this->path,
            'organization_id' => $this->resource->primary_organization_id,
            TrooperRequest::IDENTIFIER => $this->resource->identifier,
            'region_id' => match ($requested?->type)
            {
                OrganizationType::REGION => $requested->id,
                OrganizationType::UNIT => $requested->parent_id,
                default => null,
            },
            'unit_id' => $requested?->type === OrganizationType::UNIT ? $requested->id : null,
        ];
    }
}
