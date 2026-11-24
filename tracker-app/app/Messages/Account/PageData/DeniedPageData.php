<?php

declare(strict_types=1);

namespace App\Messages\Account\PageData;

use App\Enums\MembershipRole;
use App\Messages\Account\Resources\DeniedRequestResource;
use App\Messages\Auth\Resources\RegisterOrganizationResource;
use App\Messages\Organizations\Queries\GetOrganizationHierarchy;
use App\Messages\Troopers\Queries\Membership\GetDeniedTrooperRequests;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperRequest;
use App\Notifications\Troopers\TrooperDeniedNotification;
use Hyperdrive\Message;
use Illuminate\Support\Facades\DB;

/**
 * The denied application page: why it was denied, the clubs it asked for, and the clubs to
 * pick from when resubmitting.
 *
 * @method static array<string, mixed> call(Trooper $trooper)
 */
final class DeniedPageData extends Message
{
    public function __construct(
        private readonly Trooper $trooper
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $denied_requests = GetDeniedTrooperRequests::call(trooper: $this->trooper);

        $requested = Organization::whereIn(
            Organization::ID,
            $denied_requests->pluck(TrooperRequest::ORGANIZATION_ID)->unique()
        )->get()->keyBy(Organization::ID);

        $paths = Organization::buildPathLabels($requested);

        return [
            'denial_reason' => $denied_requests->first()?->denial_reason
                ?? $this->resolveDenialReasonFromNotification($this->trooper->id),
            'denied_requests' => $denied_requests
                ->map(fn (TrooperRequest $request): array => (new DeniedRequestResource(
                    $request,
                    $requested[$request->organization_id] ?? null,
                    $paths[$request->organization_id] ?? null,
                ))->resolve())
                ->values()
                ->all(),
            'organizations' => RegisterOrganizationResource::collection(GetOrganizationHierarchy::call()),
            'account_type' => $this->trooper->membership_role?->toAccountType()
                ?? MembershipRole::MEMBER->toAccountType(),
        ];
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
}
