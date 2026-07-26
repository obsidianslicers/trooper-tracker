<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\TrooperPickerMode;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Actor $actor, string $search_term, int|null $organization_id = null, bool $moderated_only = false, TrooperPickerMode $picker_mode = TrooperPickerMode::NONE)
 */
final class SearchTroopers extends Message
{
    /**
     * Summary of __construct
     *
     * @param  Actor&Trooper  $actor
     */
    public function __construct(
        private readonly Actor $actor,
        private readonly string $search_term,
        private readonly ?int $organization_id = null,
        private readonly bool $moderated_only = false,
        private readonly TrooperPickerMode $picker_mode = TrooperPickerMode::NONE) {}

    public function handle(): Collection
    {
        $search_term = trim($this->search_term);
        $query = Trooper::query();

        $admin_mode = ($this->picker_mode == TrooperPickerMode::ADMIN && $this->actor->is_administrator);

        if (!$admin_mode)
        {
            //  not an admin - so don't allow the search of ALL
            $query = $query->active()
                ->whereNotNull(Trooper::SETUP_COMPLETED_AT)
                ->where(function ($q) {
                    $q->whereNull(Trooper::GUARDIAN_ID)
                        ->orWhere(Trooper::GUARDIAN_ID, $this->actor->id);
                });
        }

        $query = $query->orderBy(Trooper::LEGAL_NAME);

        if ($this->organization_id)
        {
            $query = $query->whereHas('organizations', function ($q) {
                $q->where('tt_organizations.id', $this->organization_id);
            });
        }

        if ($this->moderated_only)
        {
            $query = $query->moderatedBy($this->actor);
        }

        $execute_query = false;
        $has_filter = $search_term !== '';

        if ($this->picker_mode == TrooperPickerMode::FRIENDS)
        {
            if (!$has_filter)
            {
                $q = TrooperFriend::query()
                    ->select(TrooperFriend::FRIEND_ID)
                    ->where(TrooperFriend::TROOPER_ID, $this->actor->id);

                $query = $query->whereIn(Trooper::ID, $q);
            }

            $execute_query = true;
        }

        if ($has_filter)
        {
            $query = $query->searchFor($search_term);

            $execute_query = true;
        }

        if ($execute_query)
        {
            return $query->get();
        }

        return collect([]);
    }
}
