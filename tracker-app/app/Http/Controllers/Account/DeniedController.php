<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Troopers\Queries\GetAvailableClubsQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DeniedController extends MagicBusController
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $trooper = $request->user();

        if (!$trooper->is_denied)
        {
            return redirect()->route($trooper->is_pending ? 'account.pending' : 'account.profile');
        }

        $denied_requests = TrooperRequest::query()
            ->where(TrooperRequest::TROOPER_ID, $trooper->id)
            ->denied()
            ->with('organization')
            ->orderBy(TrooperRequest::UPDATED_AT, 'desc')
            ->get();

        $denial_reason = $denied_requests->first()?->denial_reason
            ?? $this->resolveDenialReasonFromNotification($trooper->id);

        $available_clubs = $this->bus->send(new GetAvailableClubsQuery($trooper));

        $ancestors = $this->loadAncestors($denied_requests);
        $available_clubs_data = $this->buildAvailableClubsData($available_clubs);
        $ancestor_map_data = $this->buildAncestorMapData($available_clubs);

        $data = compact(
            'trooper',
            'denied_requests',
            'denial_reason',
            'available_clubs',
            'available_clubs_data',
            'ancestor_map_data',
            'ancestors',
        );

        return view('pages.account.denied', $data);
    }

    private function resolveDenialReasonFromNotification(int $trooper_id): ?string
    {
        $notification = DB::table('tt_notifications')
            ->where('notifiable_type', Trooper::class)
            ->where('notifiable_id', $trooper_id)
            ->where('type', TrooperDeniedNotification::class)
            ->orderByDesc('created_at')
            ->first();

        return $notification ? json_decode($notification->data, true)['body'] ?? null : null;
    }

    private function loadAncestors(Collection $denied_requests): Collection
    {
        $parse = fn ($path) => array_filter(explode(Organization::NODE_PATH_SEP, trim($path, Organization::NODE_PATH_SEP)));

        $ids = $denied_requests->flatMap(fn ($r) => $parse($r->organization->node_path))->unique()->toArray();

        return Organization::whereIn(Organization::ID, $ids)
            ->get([Organization::ID, Organization::NAME, Organization::IMAGE_PATH_SM])
            ->keyBy(Organization::ID);
    }

    private function buildAvailableClubsData(Collection $available_clubs): Collection
    {
        $root_org_ids = $available_clubs->map(function ($org) {
            $parts = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));

            return (int) reset($parts);
        })->unique()->toArray();

        $root_orgs = Organization::whereIn(Organization::ID, $root_org_ids)
            ->get([Organization::ID, Organization::IDENTIFIER_DISPLAY, Organization::IDENTIFIER_VALIDATION])
            ->keyBy(Organization::ID);

        return $available_clubs->map(function ($org) use ($root_orgs) {
            $parts = array_filter(explode(Organization::NODE_PATH_SEP, $org->node_path));
            $root = $root_orgs[(int) reset($parts)] ?? null;

            return [
                'id'                     => $org->id,
                'name'                   => $org->name,
                'parent_id'              => $org->parent_id,
                'depth'                  => $org->depth,
                'node_path'              => $org->node_path,
                'image_url'              => map_image_url($org->image_path_sm, 'img/icons/organization-128x128.png'),
                'identifier_display'     => $org->identifier_display ?? $root?->identifier_display,
                'identifier_validation'  => $org->identifier_validation ?? $root?->identifier_validation,
            ];
        });
    }

    private function buildAncestorMapData(Collection $available_clubs): array
    {
        $parse = fn ($path) => array_filter(explode(Organization::NODE_PATH_SEP, trim($path, Organization::NODE_PATH_SEP)));

        $ancestor_ids = $available_clubs->flatMap(fn ($org) => $parse($org->node_path))->unique()->map(fn ($id) => (int) $id)->toArray();

        return Organization::whereIn(Organization::ID, $ancestor_ids)
            ->get([Organization::ID, Organization::NAME, Organization::IMAGE_PATH_SM])
            ->mapWithKeys(fn ($org) => [
                $org->id => [
                    'name'      => $org->name,
                    'image_url' => map_image_url($org->image_path_sm, 'img/icons/organization-128x128.png'),
                ],
            ])
            ->all();
    }
}
