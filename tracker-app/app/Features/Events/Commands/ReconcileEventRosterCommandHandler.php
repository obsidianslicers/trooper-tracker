<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Concerns\ShouldBeTransactional;
use App\Bus\Contracts\CommandHandlerInterface;
use App\Enums\EventTrooperStatus;
use App\Mail\Events\TrooperManualSelectionStandBy;
use App\Mail\Events\TrooperNextInLine;
use App\Models\Event;
use App\Models\EventOrganization;
use App\Models\EventShift;
use App\Models\EventShiftStation;
use App\Models\EventTrooper;
use App\Models\Trooper;
use App\Services\EventRosterCapacityService;
use Illuminate\Support\Facades\Mail;

/**
 * Handler that reconciles an event roster against current capacity limits.
 *
 * Walks every shift and re-assigns GOING / STAND_BY in deterministic queue
 * order (signed_up_at ASC, id ASC) so the roster always reflects the current
 * event, organization, and station limits. Event and organization limits are
 * tracked per role (trooper vs handler); station capacity is role-agnostic,
 * mirroring EventShiftStation::hasRoom(). Because the walk is oldest-first,
 * reducing a limit demotes the newest GOING troopers first (signed_up_at
 * DESC, id DESC). Troopers promoted to GOING receive a TrooperNextInLine
 * email; troopers demoted to STAND_BY receive a TrooperManualSelectionStandBy
 * email.
 *
 * @implements CommandHandlerInterface<ReconcileEventRosterCommand>
 */
readonly class ReconcileEventRosterCommandHandler implements CommandHandlerInterface
{
    use ShouldBeTransactional;

    public function __construct(private EventRosterCapacityService $capacity) {}

    /**
     * @param  ReconcileEventRosterCommand  $message
     */
    public function __invoke(object $message): mixed
    {
        $event = $message->event;

        $event->refresh();
        $event->load([
            'event_organizations',
            'event_shifts.event_troopers.trooper',
            'event_shifts.event_shift_stations',
        ]);

        foreach ($event->event_shifts as $shift)
        {
            $this->reconcileShift($event, $shift, $message->changed_by);
        }

        return null;
    }

    private function reconcileShift(Event $event, EventShift $shift, Trooper $changed_by): void
    {
        $active = $shift->event_troopers
            ->filter(fn (EventTrooper $et) => in_array(
                $et->status,
                [EventTrooperStatus::GOING, EventTrooperStatus::STAND_BY],
            ))
            ->sortBy([
                [EventTrooper::SIGNED_UP_AT, 'asc'],
                [EventTrooper::ID, 'asc'],
            ])
            ->values();

        $going = [
            'global' => [0, 0],
            'org' => [[], []],
            'station' => [],
        ];

        foreach ($active as $et)
        {
            $new_status = $this->fits($event, $shift, $et, $going)
                ? EventTrooperStatus::GOING
                : EventTrooperStatus::STAND_BY;

            $this->applyStatus($et, $new_status, $changed_by);

            if ($new_status === EventTrooperStatus::GOING)
            {
                $this->countGoing($event, $et, $going);
            }
        }
    }

    /**
     * @param  array{global: array<int, int>, org: array<int, array<int, int>>, station: array<int, int>}  $going
     */
    private function fits(Event $event, EventShift $shift, EventTrooper $et, array $going): bool
    {
        $role = (int) $et->is_handler;
        $limit_field = $et->is_handler ? 'handlers_allowed' : 'troopers_allowed';
        $org_id = $et->effectiveOrgId($event);
        $station_id = $et->event_shift_station_id;

        $fits_global = $this->capacity->limitHasRoom($event->{$limit_field}, $going['global'][$role]);
        $fits_org = $org_id === null || $this->capacity->limitHasRoom(
            $this->orgLimit($event, $org_id, $limit_field),
            $going['org'][$role][$org_id] ?? 0,
        );

        //  station limits are never null or unlimited; a missing station record
        //  means no capacity, mirroring EventShift::stationMaxed()
        $fits_station = $station_id === null || $this->capacity->stationHasRoom(
            $this->stationLimit($shift, $station_id),
            $going['station'][$station_id] ?? 0,
        );

        return $fits_global && $fits_org && $fits_station;
    }

    /**
     * @param  array{global: array<int, int>, org: array<int, array<int, int>>, station: array<int, int>}  $going
     */
    private function countGoing(Event $event, EventTrooper $et, array &$going): void
    {
        $role = (int) $et->is_handler;
        $org_id = $et->effectiveOrgId($event);
        $station_id = $et->event_shift_station_id;

        $going['global'][$role]++;

        if ($org_id !== null)
        {
            $going['org'][$role][$org_id] = ($going['org'][$role][$org_id] ?? 0) + 1;
        }

        if ($station_id !== null)
        {
            $going['station'][$station_id] = ($going['station'][$station_id] ?? 0) + 1;
        }
    }

    private function orgLimit(Event $event, ?int $org_id, string $limit_field): ?int
    {
        if ($org_id === null)
        {
            return null;
        }

        $event_org = $event->event_organizations
            ->firstWhere(EventOrganization::ORGANIZATION_ID, $org_id);

        return $event_org?->{$limit_field};
    }

    private function stationLimit(EventShift $shift, ?int $station_id): ?int
    {
        if ($station_id === null)
        {
            return null;
        }

        $station = $shift->event_shift_stations->firstWhere(EventShiftStation::ID, $station_id);

        return $station?->troopers_allowed;
    }

    private function applyStatus(
        EventTrooper $et,
        EventTrooperStatus $new_status,
        Trooper $changed_by,
    ): void {
        if ($et->status === $new_status)
        {
            return;
        }

        $et->status = $new_status;
        $et->save();

        if ($new_status === EventTrooperStatus::GOING)
        {
            Mail::to($et->trooper->email)->queue(new TrooperNextInLine($et));

            return;
        }

        Mail::to($et->trooper->email)->queue(new TrooperManualSelectionStandBy($et, $changed_by));
    }
}
