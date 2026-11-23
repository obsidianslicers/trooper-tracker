<?php

declare(strict_types=1);

namespace App\Messages\Events\PageData\Admin;

use App\Messages\Events\Resources\EventTrooperCreditOptions;
use App\Models\Costume;
use App\Models\EventTrooper;
use App\Models\Trooper;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Support\Collection;

/**
 * The clubs a roster row can credit, limited to those the moderator looks after, and which are ticked.
 *
 * Picking a different costume ticks every club it can credit; otherwise the stored credit stays.
 *
 * @method static EventTrooperCreditOptions call(Actor $actor, EventTrooper $event_trooper, ?int $costume_id = null)
 */
final class EventTrooperOrganizationsPageData extends Message
{
    /** @param  Actor&Trooper  $actor */
    public function __construct(
        private readonly Actor $actor,
        private readonly EventTrooper $event_trooper,
        private readonly ?int $costume_id = null,
    ) {}

    public function handle(): EventTrooperCreditOptions
    {
        $this->event_trooper->load('trooper.trooper_costumes.organization_costume', 'trooper.organizations', 'costume');

        $allowed_org_ids = $this->actor->resolveModeratorOrgIds();

        [$org_options, $credited_ids] = $this->costume_id !== null
            ? $this->resolveWithCostume($this->costume_id, $allowed_org_ids)
            : $this->resolveWithoutCostume($allowed_org_ids);

        return new EventTrooperCreditOptions($org_options, $credited_ids);
    }

    private function resolveWithCostume(int $costume_id, ?array $allowed_org_ids): array
    {
        $org_options = $this->event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, Costume::find($costume_id));

        if ($costume_id !== $this->event_trooper->costume_id)
        {
            return [$org_options, $org_options->pluck('id')->all()];
        }

        return $this->storedCreditOptions($org_options);
    }

    private function resolveWithoutCostume(?array $allowed_org_ids): array
    {
        $org_options = $this->event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids);

        if ($this->event_trooper->costume_id !== null)
        {
            return [$org_options, $org_options->pluck('id')->all()];
        }

        return $this->storedCreditOptions($org_options);
    }

    private function storedCreditOptions(Collection $org_options): array
    {
        return [
            $this->event_trooper->withCreditedRootOrgOptions($org_options),
            $this->event_trooper->creditedRootOrgIds(),
        ];
    }
}
