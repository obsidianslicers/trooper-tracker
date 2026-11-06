<?php

declare(strict_types=1);

namespace App\Messages\Events\Queries\Verification;

use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\Trooper;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Troops whose credit is waiting for verification and that the moderator may review.
 * Each row gets reviewable_organizations: the requested clubs this moderator covers.
 */
final class GetTroopsAwaitingVerification extends Message
{
    public function __construct(
        private readonly Trooper $moderator,
    ) {}

    /**
     * @return Collection<int, EventTrooper>
     */
    public function handle(): Collection
    {
        $rows = EventTrooper::query()
            ->whereNotNull(EventTrooper::REQUESTED_ORGANIZATION_IDS)
            ->whereHas('trooper')
            ->with(['trooper', 'costume', 'event_shift.event'])
            ->orderBy(EventTrooper::VERIFICATION_REQUESTED_AT)
            ->get();

        $organizations = Organization::whereIn(
            Organization::ID,
            $rows->flatMap(fn (EventTrooper $et) => $et->requested_organization_ids ?? [])->unique()->all()
        )->get()->keyBy(Organization::ID);

        return $rows
            ->each(function (EventTrooper $et) use ($organizations): void
            {
                $et->reviewable_organizations = collect($et->verifiableOrganizationIdsFor($this->moderator))
                    ->map(fn (int $id) => $organizations->get($id))
                    ->filter()
                    ->values();
            })
            ->filter(fn (EventTrooper $et) => $et->reviewable_organizations->isNotEmpty())
            ->values();
    }
}
