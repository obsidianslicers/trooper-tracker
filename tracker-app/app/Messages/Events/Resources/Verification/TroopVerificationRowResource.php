<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Verification;

use App\Models\EventTrooper;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A row of the Troop Verifications queue: the troop, the costume worn, and the clubs the
 * moderator may give credit for. Load it through `GetTroopsAwaitingVerification`.
 *
 * @mixin EventTrooper
 */
class TroopVerificationRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->event_shift?->event;

        return [
            EventTrooper::ID => $this->id,
            'trooper' => ['id' => $this->trooper->id, 'name' => $this->trooper->display_name],
            'event' => $event ? ['id' => $event->id, 'name' => $event->name] : null,
            'starts_at' => $this->event_shift?->shift_starts_at?->localFormat('M j, Y'),
            'costume' => $this->costumeLabel(),
            'has_costume' => $this->costume !== null,
            'correction' => $this->pending_troop_correction
                ? new TroopCorrectionSummaryResource($this->pending_troop_correction)
                : null,
            'clubs' => $this->reviewable_organizations
                ->map(fn (Organization $organization) => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                ])
                ->values(),
        ];
    }

    private function costumeLabel(): string
    {
        return match (true)
        {
            $this->costume !== null => $this->costume->name,
            (bool) $this->is_attending_without_costume => __('admin.No costume'),
            default => __('admin.None picked'),
        };
    }
}
