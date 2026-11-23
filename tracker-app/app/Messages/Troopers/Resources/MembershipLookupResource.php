<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A membership request's identifier checked against its club (the approvals page's
 * components/membership/MembershipLookup): what the club's member lookup found, and any other
 * trooper already holding that identifier.
 *
 * @mixin TrooperRequest
 */
class MembershipLookupResource extends JsonResource
{
    /** The member lookup fields the card shows (MemberLookupInterface, without the raw identifier). */
    private const MEMBER_FIELDS = [
        'formatted_identifier',
        'full_name',
        'status',
        'standing',
        'is_approved',
        'unit_name',
        'profile_url',
        'thumbnail_url',
    ];

    /**
     * @param  array<string, mixed>|null  $member  what the club's member lookup returned
     */
    public function __construct(
        TrooperRequest $trooper_request,
        private readonly ?string $service_name,
        private readonly ?array $member,
        private readonly ?Trooper $existing_trooper,
    ) {
        parent::__construct($trooper_request);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            TrooperRequest::IDENTIFIER => $this->identifier,
            'primary_organization' => [
                Organization::NAME => $this->primary_organization->name,
            ],
            'existing_trooper_membership' => $this->existing_trooper === null ? null : [
                Trooper::ID => $this->existing_trooper->id,
                Trooper::LEGAL_NAME => $this->existing_trooper->legal_name,
            ],
            'service_name' => $this->service_name,
            'member' => $this->member === null
                ? null
                : array_intersect_key($this->member, array_flip(self::MEMBER_FIELDS)),
        ];
    }
}
