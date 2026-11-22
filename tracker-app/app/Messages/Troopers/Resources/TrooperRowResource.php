<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Resources;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Command Staff troopers list.
 *
 * @mixin Trooper
 */
class TrooperRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            Trooper::ID => $this->id,
            Trooper::DISPLAY_NAME => $this->display_name,
            Trooper::LEGAL_NAME => $this->legal_name,
            Trooper::EMAIL => str_starts_with((string) $this->email, '^') ? null : $this->email,
            'is_active' => $this->is_active,
            'is_invalid' => $this->membership_status === MembershipStatus::INVALID,
            'is_departed' => $this->membership_status === MembershipStatus::DEPARTED,
            'role' => [
                'value' => $this->membership_role->value,
                'label' => $this->membership_role->label(),
            ],
            'status' => $this->membership_status->label(),
            'last_seen' => $this->last_active_at?->localFormat('M d, Y'),
            'is_long_gone' => $this->last_active_at?->lt(now()->subYear()) ?? false,
        ];
    }
}
