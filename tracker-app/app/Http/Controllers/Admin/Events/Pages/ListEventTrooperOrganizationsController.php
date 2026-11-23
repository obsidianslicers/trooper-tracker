<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Events\Pages;

use App\Http\Controllers\Controller;
use App\Messages\Events\Resources\EventTrooperCreditOptions;
use App\Models\Costume;
use App\Models\Event;
use App\Models\EventTrooper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The clubs a roster row can credit for a chosen costume, and which of them to tick.
 */
class ListEventTrooperOrganizationsController extends Controller
{
    public function __invoke(Request $request, Event $event, EventTrooper $event_trooper): JsonResponse
    {
        $this->authorize('update', $event);
        abort_if($event_trooper->event_shift->event_id !== $event->id, 404);

        $event_trooper->load('trooper.trooper_costumes.organization_costume', 'trooper.organizations', 'costume');

        $costume_id = $request->input('costume_id') !== null && $request->input('costume_id') !== ''
            ? (int) $request->input('costume_id')
            : null;
        $allowed_org_ids = $request->user()->resolveModeratorOrgIds();

        [$org_options, $credited_ids] = $costume_id !== null
            ? $this->resolveWithCostume($event_trooper, $costume_id, $allowed_org_ids)
            : $this->resolveWithoutCostume($event_trooper, $allowed_org_ids);

        return (new EventTrooperCreditOptions($org_options, $credited_ids))->response($request);
    }

    private function resolveWithCostume(EventTrooper $event_trooper, int $costume_id, ?array $allowed_org_ids): array
    {
        $costume = Costume::find($costume_id);
        $is_new_costume = $costume_id !== $event_trooper->costume_id;

        $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids, $costume);

        if ($is_new_costume)
        {
            return [$org_options, $org_options->pluck('id')->all()];
        }

        return $this->storedCreditOptions($event_trooper, $org_options);
    }

    private function resolveWithoutCostume(EventTrooper $event_trooper, ?array $allowed_org_ids): array
    {
        $org_options = $event_trooper->eligibleRootOrgsForAdmin($allowed_org_ids);

        if ($event_trooper->costume_id !== null)
        {
            return [$org_options, $org_options->pluck('id')->all()];
        }

        return $this->storedCreditOptions($event_trooper, $org_options);
    }

    /**
     * Options and ticked ids for the row's stored credit, including clubs the trooper has since
     * left.
     */
    private function storedCreditOptions(
        EventTrooper $event_trooper,
        Collection $org_options
    ): array {
        return [
            $event_trooper->withCreditedRootOrgOptions($org_options),
            $event_trooper->creditedRootOrgIds(),
        ];
    }
}
