<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Trooper $resource
 */
class AdminTrooperDetails extends JsonResource
{
    public function __construct(Trooper $trooper, private readonly Trooper $actor)
    {
        parent::__construct($trooper);
    }

    /**
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        $trooper = $this->resource;

        return [
            'legal_name' => $trooper->legal_name,
            'display_name' => $trooper->display_name,
            'email' => $trooper->email,
            'phone' => $trooper->phone,
            'membership_status' => $trooper->membership_status->value,
            'membership_status_options' => $this->getStatusOptions(),
            'last_active_at' => $trooper->last_active_at?->format('D - M d, Y') ?? 'Never',
            'is_invalid' => $trooper->membership_status === MembershipStatus::INVALID,
            'is_departed' => $trooper->membership_status === MembershipStatus::DEPARTED,
            'is_untrusted' => (bool) $trooper->is_untrusted,
            'untrusted_reason' => $trooper->untrusted_reason,
            'can_mark_untrusted' => $this->actor->can('markUntrusted', $trooper),
            'can_void' => $this->actor->can('void', $trooper),
            'can_unvoid' => $this->actor->can('unvoid', $trooper),
            'can_mark_rip' => $this->actor->can('markRip', $trooper),
            'can_unmark_rip' => $this->actor->can('unmarkRip', $trooper),
            'trooper_stamps' => new TrooperStampsResource($trooper),
        ];
    }

    /**
     * @return array<int, array{value:string,label:string}>
     */
    private function getStatusOptions(): array
    {
        $choices = MembershipStatus::choicesFor(
            $this->actor->is_administrator,
            $this->resource->membership_status
        );

        $options = [];

        foreach ($choices as $value => $label)
        {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
