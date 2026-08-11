<?php

declare(strict_types=1);

namespace App\Messages\Troopers\Commands;

use App\Models\Trooper;
use Hyperdrive\Message;
use App\Models\TrooperAssignment;

/**
 * Command message for updating a trooper's organization notifications setting.
 * 
 * @method static void call(Trooper $trooper, bool $enabled)
 */
final class UpdateOrganizationNotifications extends Message
{
    public function __construct(
        private readonly Trooper $trooper,
        private readonly array $organization_ids,
        private readonly bool $enabled,
    ) {
    }

    /**
     * Execute the command to update trooper organization notifications setting.
     *
     * @return null
     */
    public function handle(): void
    {
        foreach ($this->organization_ids as $organization_id)
        {
            $this->updateAssignment($organization_id);
        }
    }

    private function updateAssignment(int $organization_id): void
    {
        $trooper_assignment = $this->trooper->trooper_assignments()
            ->where(TrooperAssignment::ORGANIZATION_ID, $organization_id)
            ->first();

        if ($trooper_assignment)
        {
            $trooper_assignment->should_notify = $this->enabled;
        }
        else
        {
            $trooper_assignment = new TrooperAssignment();
            $trooper_assignment->trooper_id = $this->trooper->id;
            $trooper_assignment->organization_id = $organization_id;
            $trooper_assignment->should_notify = $this->enabled;
        }

        $trooper_assignment->save();
    }
}
