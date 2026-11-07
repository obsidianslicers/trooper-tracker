<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Costumes;

use App\Enums\MembershipRole;
use App\Messages\Troopers\Queries\GetTroopersByRole;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Notifications\Admin\CostumeRequestSubmittedNotification;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * Tells administrators and in-scope moderators that a trooper added a costume
 * that needs their approval. Each officer only hears about the clubs they can review,
 * and rows an officer already handled are skipped.
 *
 * @method static void call(Trooper $trooper, array $trooper_costume_ids)
 */
final class SendCostumeRequestNotifications extends Message
{
    /**
     * @param  array<int, int>  $trooper_costume_ids  The newly pending trooper costume rows
     */
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $trooper_costume_ids,
    ) {}

    public function handle(): void
    {
        $officers = GetTroopersByRole::call(membership_role: MembershipRole::ADMINISTRATOR)
            ->merge(GetTroopersByRole::call(membership_role: MembershipRole::MODERATOR))
            ->unique('id');

        foreach ($officers as $officer)
        {
            $reviewable = $this->reviewableBy($officer);

            if ($reviewable->isEmpty())
            {
                continue;
            }

            $officer->notify(new CostumeRequestSubmittedNotification(
                trooper_name: $this->trooper->display_name,
                costume_name: $reviewable->first()->organization_costume->costume->name,
                club_names: $reviewable->map(
                    fn (TrooperCostume $row): string => $row->organization_costume->organization->name
                )->unique()->values()->all(),
            ));
        }
    }

    /**
     * @return Collection<int, TrooperCostume>
     */
    private function reviewableBy(Trooper $officer): Collection
    {
        return TrooperCostume::onlyPending()
            ->whereIn(TrooperCostume::ID, $this->trooper_costume_ids)
            ->moderatedBy($officer)
            ->with('organization_costume.costume', 'organization_costume.organization')
            ->get();
    }
}
