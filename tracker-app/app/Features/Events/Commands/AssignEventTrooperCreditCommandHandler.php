<?php

declare(strict_types=1);

namespace App\Features\Events\Commands;

use App\Bus\Contracts\CommandHandlerInterface;
use App\Features\Events\Concerns\AssignsEventTrooperOrgCredit;
use App\Models\EventTrooper;

/**
 * @implements CommandHandlerInterface<AssignEventTrooperCreditCommand>
 */
readonly class AssignEventTrooperCreditCommandHandler implements CommandHandlerInterface
{
    use AssignsEventTrooperOrgCredit;

    public function __invoke(object $message): EventTrooper
    {
        $event_trooper = $message->event_trooper;
        $event_trooper->loadMissing('costume');

        $costumes_by_id = $event_trooper->costume_id !== null
            ? collect([$event_trooper->costume_id => $event_trooper->costume])
            : collect();

        $allowed_org_ids = $message->actor->resolveModeratorOrgIds();

        $has_submitted_org_selection = $this->applyCostumeAndOrgSelection(
            $event_trooper,
            [
                'costume_id' => $event_trooper->costume_id,
                'organization_ids' => $message->organization_ids,
            ],
            $allowed_org_ids,
            $costumes_by_id
        );

        if ($has_submitted_org_selection)
        {
            $event_trooper->organization_id = null;
        }

        $event_trooper->save();

        return $event_trooper;
    }
}
