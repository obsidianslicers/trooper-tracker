<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources\Display;

use App\Enums\EventTrooperStatus;
use App\Support\SignUpTour;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The event page's "How sign-up works" tour: whether it starts by itself, and what its example
 * shows (a costume, the plans to choose from, a mission brief still to read).
 *
 * @property EventDisplayContext $resource
 */
class EventDisplaySignUpTour extends JsonResource
{
    /**
     * @return array{auto_start: bool, storage_key: string, requires_costume: bool, has_plans: bool, plans: list<string>, going_label: string, is_brief_pending: bool}
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource->event;
        $viewer = $this->resource->viewer;
        $plans = array_diff_key(
            $event->signUpStatusOptions(EventTrooperStatus::GOING),
            [EventTrooperStatus::CANCELLED->value => true],
        );

        return [
            'auto_start' => SignUpTour::startsFor($viewer, $event),
            'storage_key' => 'tt.signupTourSeen.'.$viewer->id,
            'requires_costume' => $event->requiresCostume(),
            'has_plans' => count($plans) > 1,
            'plans' => array_values($plans),
            'going_label' => EventTrooperStatus::GOING->label(),
            'is_brief_pending' => !$this->resource->has_brief_ack,
        ];
    }
}
