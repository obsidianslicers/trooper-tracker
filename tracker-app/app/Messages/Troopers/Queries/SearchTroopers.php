<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Enums\TrooperPickerMode;
use App\Models\Filters\TrooperFilter;
use App\Models\Trooper;
use App\Models\TrooperFriend;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $trooper, TrooperFilter $filter, int|null $organization_id = null, bool $moderated_only = false, TrooperPickerMode $picker_mode = TrooperPickerMode::NONE)
 */
final class SearchTroopers extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly TrooperFilter $filter,
        private readonly int|null $organization_id = null,
        private readonly bool $moderated_only = false,
        private readonly TrooperPickerMode $picker_mode = TrooperPickerMode::NONE)
    {
    }

    public function handle(): Collection
    {
        $query = Trooper::active()
            ->whereNotNull(Trooper::SETUP_COMPLETED_AT)
            ->where(function ($q)
            {
                $q->whereNull(Trooper::GUARDIAN_ID)
                    ->orWhere(Trooper::GUARDIAN_ID, $this->trooper->id);
            })
            ->orderBy(Trooper::DISPLAY_NAME);

        if ($this->organization_id)
        {
            $query = $query->whereHas('organizations', function ($q)
            {
                $q->where('tt_organizations.id', $this->organization_id);
            });
        }

        if ($this->moderated_only)
        {
            $query = $query->moderatedBy($this->trooper);
        }

        $execute_query = false;
        $has_filter = $this->filter->hasFilter();

        if ($this->picker_mode == TrooperPickerMode::FRIENDS)
        {
            if (!$has_filter)
            {
                $q = TrooperFriend::query()
                    ->select(TrooperFriend::FRIEND_ID)
                    ->where(TrooperFriend::TROOPER_ID, $this->trooper->id);

                $query = $query->whereIn(Trooper::ID, $q);
            }

            $execute_query = true;
        }

        if ($has_filter)
        {
            $query = $query->filterWith($this->filter);

            $execute_query = true;
        }

        if ($execute_query)
        {
            return $query->get();
        }

        return collect([]);
    }
}
