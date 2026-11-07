<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Enums\MembershipRole;
use App\Enums\OrganizationType;
use App\Messages\Organizations\Queries\GetOrganizationTree;
use App\Messages\Troopers\Queries\Membership\GetDeniedTrooperRequests;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * Aggregates data needed for the Denied membership page.
 *
 * Retrieves denied trooper requests, denial reasons, organization hierarchy,
 * and prepares form state for resubmission.
 *
 * @method static array call(Trooper $trooper)
 */
final class DeniedPageData extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    /**
     * Retrieve page data for the denied membership page.
     *
     * @return array<string, mixed> Page data including trooper, denied requests, organization hierarchy, etc.
     */
    public function handle(): array
    {
        $denied_requests = GetDeniedTrooperRequests::call(trooper: $this->trooper);

        $denial_reason = $denied_requests->first()?->denial_reason
            ?? $this->resolveDenialReasonFromNotification($this->trooper->id);

        $specific_orgs = Organization::whereIn(
            Organization::ID,
            $denied_requests->pluck(TrooperRequest::ORGANIZATION_ID)->unique()
        )->get()->keyBy(Organization::ID);

        $org_paths = Organization::buildPathLabels($specific_orgs);

        $organization_hierarchy = GetOrganizationTree::call()
            ->map(fn (array $org) => (object) $org);

        $request_by_primary = $denied_requests->keyBy(TrooperRequest::PRIMARY_ORGANIZATION_ID);

        foreach ($organization_hierarchy as $org)
        {
            $denied = $request_by_primary[$org->id] ?? null;
            $specific = $denied ? ($specific_orgs[$denied->organization_id] ?? null) : null;

            $org->selected = old("organizations.{$org->id}.selected") === '1'
                || ($denied !== null && !old('organizations'));
            $org->identifier = old("organizations.{$org->id}.identifier", $denied?->identifier);
            $org->region_id = old("organizations.{$org->id}.region_id", $this->resolveRegionId($specific));
            $org->unit_id = old("organizations.{$org->id}.unit_id", $this->resolveUnitId($specific));
        }

        $account_type = $this->trooper->membership_role?->toAccountType() ?? MembershipRole::MEMBER->toAccountType();

        return compact(
            'denied_requests',
            'denial_reason',
            'specific_orgs',
            'org_paths',
            'organization_hierarchy',
            'account_type',
        );
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

    private function resolveRegionId(?Organization $org): ?int
    {
        return match ($org?->type)
        {
            OrganizationType::REGION => $org->id,
            OrganizationType::UNIT => $org->parent_id,
            default => null,
        };
    }

    private function resolveUnitId(?Organization $org): ?int
    {
        return $org?->type === OrganizationType::UNIT ? $org->id : null;
    }
}
