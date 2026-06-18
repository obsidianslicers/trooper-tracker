<?php

declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Features\Organizations\Queries\GetOrganizationHierarchyQuery;
use App\Http\Controllers\MagicBusController;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $organization_hierarchy = $this->bus->send(new GetOrganizationHierarchyQuery)
            ->map(fn (array $org) => (object) $org);

        $request_by_primary = $denied_requests->keyBy(TrooperRequest::PRIMARY_ORGANIZATION_ID);

        foreach ($organization_hierarchy as $org)
        {
            $denied = $request_by_primary[$org->id] ?? null;

            $org->selected   = old("organizations.{$org->id}.selected") === '1'
                               || ($denied !== null && !old('organizations'));
            $org->identifier = old("organizations.{$org->id}.identifier", $denied?->identifier);
            $org->region_id  = old("organizations.{$org->id}.region_id", $this->resolveRegionId($denied));
            $org->unit_id    = old("organizations.{$org->id}.unit_id", $this->resolveUnitId($denied));
        }

        $account_type = $this->resolveAccountType($trooper);

        $data = compact(
            'trooper',
            'denied_requests',
            'denial_reason',
            'organization_hierarchy',
            'account_type',
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

    private function resolveRegionId(?TrooperRequest $request): ?int
    {
        if ($request === null)
        {
            return null;
        }

        $org = $request->organization;

        if ($org->depth === 1)
        {
            return $org->id;
        }

        if ($org->depth === 2)
        {
            return $org->parent_id;
        }

        return null;
    }

    private function resolveUnitId(?TrooperRequest $request): ?int
    {
        if ($request === null)
        {
            return null;
        }

        return $request->organization->depth === 2 ? $request->organization->id : null;
    }

    private function resolveAccountType(mixed $trooper): string
    {
        $role = $trooper->membership_role?->value ?? 'member';

        return in_array($role, ['visitor', 'handler'], true) ? $role : 'member';
    }
}
