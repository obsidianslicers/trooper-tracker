<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Models\TrooperAssignment;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's organization assignment setting.
 *
 * @method static void call(int $trooper_id, int $organization_id, bool $is_member)
 */
final class CreateOrUpdateOrganizationAssignment extends Message
{
    public function __construct(
        private readonly int $trooper_id,
        private readonly int $organization_id,
        private readonly bool $is_member,
    ) {
    }

    /**
     * Execute the command to update trooper organization assignment setting.
     *
     * @return null
     */
    public function handle(): void
    {
        $trooper_assignment = TrooperAssignment::query()
            ->withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $this->trooper_id)
            ->where(TrooperAssignment::ORGANIZATION_ID, $this->organization_id)
            ->first();

        if ($trooper_assignment)
        {
            if ($trooper_assignment->trashed())
            {
                $trooper_assignment->restore();
            }

            $trooper_assignment->is_member = $this->is_member;
        }
        else
        {
            $trooper_assignment = new TrooperAssignment;
            $trooper_assignment->trooper_id = $this->trooper_id;
            $trooper_assignment->organization_id = $this->organization_id;
            $trooper_assignment->is_member = $this->is_member;
        }

        $trooper_assignment->save();
    }
}
