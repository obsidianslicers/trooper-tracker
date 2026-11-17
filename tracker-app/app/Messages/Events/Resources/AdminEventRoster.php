<?php

declare(strict_types=1);

namespace App\Messages\Events\Resources;

use App\Enums\EventGuestStatus;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventGuest;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

/**
 * The roster for the Roster tab: each shift's troopers and guests, with the choices
 * each row offers.
 *
 * @property Event $resource
 */
class AdminEventRoster extends JsonResource
{
    /**
     * @param  Collection<int, EventShift>  $shifts  From GetTroopersForEventAdmin.
     */
    public function __construct(
        Event $event,
        private readonly Trooper $actor,
        private readonly Collection $shifts,
    ) {
        parent::__construct($event);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $event = $this->resource;

        return [
            'is_manual_selection' => $event->status === EventStatus::MANUAL_SELECTION,
            'has_station_shifts' => $this->shifts->contains(fn (EventShift $s): bool => $s->usesStations()),
            'can_manage' => $this->actor->is_administrator
                || ($event->organization !== null
                    && $this->actor->isModeratorForOrganization($event->organization)),
            'status_options' => $this->options(EventTrooperStatus::toArray()),
            'guest_status_options' => $this->options(EventGuestStatus::toArray()),
            'shifts' => $this->shifts->map(fn (EventShift $shift): array => [
                'id' => $shift->id,
                'time_display' => $shift->time_display,
                'uses_stations' => $shift->usesStations(),
                'station_options' => $shift->event_shift_stations
                    ->map(fn (EventShiftStation $s): array => ['value' => $s->id, 'label' => $s->name])
                    ->values()
                    ->all(),
                'troopers' => $shift->event_troopers->map(fn (EventTrooper $et): array => $this->trooper($et))
                    ->values()
                    ->all(),
                'guests' => $shift->event_guests->map(fn (EventGuest $guest): array => $this->guest($guest))
                    ->values()
                    ->all(),
            ])->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function trooper(EventTrooper $event_trooper): array
    {
        $trooper = $event_trooper->trooper;

        return [
            'id' => $event_trooper->id,
            'trooper_id' => $trooper->id,
            'display_name' => $trooper->display_name,
            'legal_name' => $trooper->legal_name,
            'profile_url' => $this->actor->is_event_coordinator
                ? route('service-records.trooper', ['trooper' => $trooper->id])
                : route('admin.troopers.profile', ['trooper' => $trooper->id]),
            'costume_id' => $event_trooper->costume_id,
            'costume_options' => $this->options($event_trooper->costume_options ?? []),
            'backup_costume' => $event_trooper->backup_costume?->name,
            'status' => $event_trooper->status->value,
            'event_shift_station_id' => $event_trooper->event_shift_station_id,
            'org_options' => self::orgOptions($event_trooper->org_options ?? collect()),
            'organization_ids' => array_values($event_trooper->credited_checked_ids ?? []),
            ...$this->approval(
                $event_trooper->status === EventTrooperStatus::GOING,
                $event_trooper->updated_by,
                $event_trooper->updated_at,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function guest(EventGuest $guest): array
    {
        return [
            'id' => $guest->id,
            'name' => $guest->name,
            'added_by' => $guest->added_by_trooper?->display_name,
            'status' => $guest->status->value,
            ...$this->approval(
                $guest->status === EventGuestStatus::GOING,
                $guest->updated_by,
                $guest->updated_at,
            ),
        ];
    }

    /**
     * Who moved a manual selection signup to Going, and when.
     *
     * @return array{approved_by:?string,approved_at:?string}
     */
    private function approval(bool $is_going, ?Trooper $updated_by, mixed $updated_at): array
    {
        return [
            'approved_by' => $is_going ? $updated_by?->display_name : null,
            'approved_at' => $is_going ? $updated_at?->localFormat('M j, Y g:ia') : null,
        ];
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     * @return array<int, array{id:int,name:string}>
     */
    public static function orgOptions(Collection $organizations): array
    {
        return $organizations
            ->map(fn (Organization $org): array => ['id' => $org->id, 'name' => $org->name])
            ->values()
            ->all();
    }

    /**
     * @param  array<string|int, string>  $choices
     * @return array<int, array{value:string|int,label:string}>
     */
    private function options(array $choices): array
    {
        $options = [];

        foreach ($choices as $value => $label)
        {
            $options[] = ['value' => $value, 'label' => $label];
        }

        return $options;
    }
}
