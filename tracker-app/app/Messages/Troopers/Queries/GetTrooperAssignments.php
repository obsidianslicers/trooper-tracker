<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Queries;

use App\Models\Trooper;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * @method static Collection call(Trooper $trooper, bool $member_only)
 */
final class GetTrooperAssignments extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly bool $member_only = false,
    ) {}

    public function handle(): Collection
    {
        $query = TrooperAssignment::query()
            ->with(['organization'])
            ->where(TrooperAssignment::TROOPER_ID, $this->trooper->id);

        if ($this->member_only)
        {
            $query = $query->where(TrooperAssignment::IS_MEMBER, true);
        }

        return $query->get();
    }
}
