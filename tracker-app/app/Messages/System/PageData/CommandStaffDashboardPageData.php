<?php

declare(strict_types=1);

namespace App\Messages\System\PageData;

use App\Enums\MembershipStatus;
use App\Messages\Events\Queries\Verification\GetTroopsAwaitingVerification;
use App\Messages\System\Queries\GetSystemCheckProblems;
use App\Models\Costume;
use App\Models\EventType;
use App\Models\PermissionRequest;
use App\Models\Trooper;
use App\Models\TrooperCostume;
use App\Models\TrooperRequest;
use App\Plugins\PluginSlots;
use Hyperdrive\Contracts\Actor;
use Hyperdrive\Message;
use Illuminate\Http\Request;

/**
 * Data for the Command Staff dashboard: what's waiting for review, and the tools the reader can
 * open. Each card is named by a key the page turns into its title and text.
 *
 * @method static array<string, mixed> call(Actor $actor)
 */
final class CommandStaffDashboardPageData extends Message
{
    /**
     * @param  Actor&Trooper  $actor
     */
    public function __construct(private readonly Actor $actor) {}

    /**
     * @return array<string, mixed>
     */
    public function handle(PluginSlots $slots, Request $request): array
    {
        $counts = $this->counts();

        return [
            'trooper_id' => $this->actor->id,
            'counts' => $counts,
            'system_check_problems' => $this->actor->has_system_owner
                ? GetSystemCheckProblems::call()->count()
                : 0,
            'cards' => $this->cards(),
            'plugin_cards' => $slots->render('admin.dashboard.cards', [
                'request' => $request,
                'actor' => $this->actor,
                ...$counts,
            ]),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        $trooper = $this->actor;

        return [
            'not_approved' => Trooper::pendingApprovals()->moderatedBy($trooper)->count(),
            'pending_join_requests' => TrooperRequest::pending()
                ->whereHas('trooper', fn ($query) => $query
                    ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE))
                ->forModerator($trooper)
                ->count(),
            'pending_costumes' => TrooperCostume::onlyPending()
                ->moderatedBy($trooper)
                ->whereHas('trooper')
                ->count(),
            'troops_awaiting_verification' => GetTroopsAwaitingVerification::call(moderator: $trooper)
                ->count(),
            'pending_permission_requests' => $trooper->is_administrator
                ? PermissionRequest::pending()->count()
                : 0,
        ];
    }

    /**
     * The tools the reader can open, in the order the dashboard shows them.
     *
     * @return array<int, array{key: string, url: string}>
     */
    private function cards(): array
    {
        $trooper = $this->actor;
        $active = $trooper->membership_status === MembershipStatus::ACTIVE;
        $administrator = $active && $trooper->is_administrator;
        $owner = $active && $trooper->has_system_owner;

        $cards = [
            'trooper-approvals' => ['admin.troopers.approvals.index', true],
            'costume-approvals' => ['admin.troopers.costume-approvals.index', true],
            'troop-verifications' => ['admin.troopers.troop-verifications.index', true],
            'troopers' => ['admin.troopers.index', true],
            'missing-credit' => ['admin.service-records.missing-credits.index', true],
            'request-permissions' => ['admin.troopers.permissions.create', true],
            'permission-approvals' => ['admin.troopers.permissions.approvals.index', $administrator],
            'merge-troopers' => ['admin.troopers.merge.create', $owner],
            'notices' => ['admin.notices.index', true],
            'events' => ['admin.events.index', true],
            'event-types' => ['admin.event-types.index', $trooper->can('viewAny', EventType::class)],
            'awards' => ['admin.awards.index', true],
            'costumes' => ['admin.costumes.index', $trooper->can('create', Costume::class)],
            'merge-costumes' => ['admin.costumes.merge.create', $owner],
            'organizations' => ['admin.organizations.index', true],
            'reports' => ['admin.reports.index', true],
            'faq' => ['admin.faq.index', $administrator],
            'system-check' => ['admin.system-check', $owner],
            'plugins' => ['admin.plugins.index', $owner],
        ];

        return collect($cards)
            ->filter(fn (array $card): bool => $card[1])
            ->map(fn (array $card, string $key): array => ['key' => $key, 'url' => route($card[0])])
            ->values()
            ->all();
    }
}
