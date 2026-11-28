<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Corrections;

use App\Models\Organization;
use App\Models\TroopCorrection;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A request for troop credit as its event coordinator sees it: who asked, for which troop and
 * clubs, what they said, and whether it still waits for this coordinator's answer.
 *
 * @mixin TroopCorrection
 */
class TroopCorrectionReviewResource extends JsonResource
{
    public function __construct(TroopCorrection $troop_correction, private readonly Trooper $viewer)
    {
        parent::__construct($troop_correction);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event_trooper = $this->event_trooper;

        return [
            TroopCorrection::ID => $this->id,
            'trooper_name' => $this->trooper->display_name,
            'event' => ['id' => $this->event->id, 'name' => $this->event->name],
            'shift' => $event_trooper->event_shift?->time_display,
            'costume' => $event_trooper->costume?->name,
            'clubs' => $this->clubNames(),
            TroopCorrection::DETAILS => $this->details,
            TroopCorrection::STATUS => $this->status->value,
            'can_answer' => $this->isAwaitingCoordinatorFrom($this->viewer),
        ];
    }

    /**
     * @return list<string>
     */
    private function clubNames(): array
    {
        $event_trooper = $this->event_trooper;
        $ids = array_merge(
            $event_trooper->requested_organization_ids ?? [],
            $event_trooper->costume_organization_ids ?? [],
        );

        return Organization::whereIn(Organization::ID, $ids)
            ->get()
            ->map(fn (Organization $organization): string => $organization->getPrimaryClub()->name)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}
