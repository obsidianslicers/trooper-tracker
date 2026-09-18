<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands\Membership;

use App\Models\Organization;
use App\Models\TrooperAssignment;
use Hyperdrive\Message;

/**
 * Command message for updating a trooper's organization assignment setting.
 *
 * @method static void call(int $trooper_id)
 */
final class ClearOrganizationAssignments extends Message
{
    public function __construct(
        private readonly int $trooper_id,
        private readonly Organization $primary_organization
    ) {
    }

    /**
     * Execute the command to update trooper organization assignment setting.
     *
     * @return null
     */
    public function handle(): void
    {
        TrooperAssignment::query()
            ->withTrashed()
            ->where(TrooperAssignment::TROOPER_ID, $this->trooper_id)
            ->whereHas('organization', function ($q): void
            {
                $q->where(Organization::NODE_PATH, 'like', $this->primary_organization->node_path . '%');
            })
            ->update([
                TrooperAssignment::IS_MEMBER => false,
            ]);
    }
}
