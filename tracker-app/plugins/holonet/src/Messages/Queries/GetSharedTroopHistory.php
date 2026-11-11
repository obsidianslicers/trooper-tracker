<?php

declare(strict_types=1);

namespace TroopTracker\Plugins\Holonet\Messages\Queries;

use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Models\Event;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The troops this tracker recorded for a trooper, as shared with a linked
 * tracker: attended shifts of closed events, with only what a service record
 * needs. Troops copied here from other trackers are never shared on.
 *
 * @method static array<int, array<string, mixed>> call(Trooper $trooper)
 */
final class GetSharedTroopHistory extends Message
{
    /** @var array<int, string|null> */
    private array $club_names = [];

    public function __construct(private readonly Trooper $trooper) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function handle(): array
    {
        $signups = EventTrooper::query()
            ->where(EventTrooper::TROOPER_ID, $this->trooper->id)
            ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED->value)
            ->whereHas('event_shift.event', fn ($query) => $query
                ->where(Event::STATUS, EventStatus::CLOSED->value)
                ->counted())
            ->with(['event_shift.event.event_type', 'costume'])
            ->orderBy(EventTrooper::ID)
            ->get();

        $this->loadClubNames($signups);

        return $signups->map(fn (EventTrooper $signup): array => $this->record($signup))->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function record(EventTrooper $signup): array
    {
        $shift = $signup->event_shift;
        $event = $shift->event;

        return [
            'id' => (string) $signup->id,
            'event_name' => $event->name,
            'event_type' => $event->event_type?->name,
            'venue' => $event->venue,
            'shift_starts_at' => $shift->shift_starts_at->toIso8601String(),
            'shift_ends_at' => $shift->shift_ends_at->toIso8601String(),
            'costume' => $signup->costume?->name,
            'clubs' => $this->clubsFor($signup),
            'is_handler' => $signup->is_handler || $signup->is_attending_without_costume,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function clubsFor(EventTrooper $signup): array
    {
        $ids = $this->creditedIds($signup);

        return collect($ids)
            ->map(fn (int $id): ?string => $this->club_names[$id] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function creditedIds(EventTrooper $signup): array
    {
        $ids = array_map('intval', (array) ($signup->costume_organization_ids ?? []));

        if ($ids === [] && $signup->organization_id !== null)
        {
            $ids = [$signup->organization_id];
        }

        return $ids;
    }

    /**
     * @param  Collection<int, EventTrooper>  $signups
     */
    private function loadClubNames(Collection $signups): void
    {
        $ids = $signups->flatMap(fn (EventTrooper $signup): array => $this->creditedIds($signup))
            ->unique()
            ->values();

        Organization::query()->whereIn(Organization::ID, $ids)->get()
            ->each(function (Organization $organization): void
            {
                $this->club_names[$organization->id] = $organization->getPrimaryClub()->name;
            });
    }
}
