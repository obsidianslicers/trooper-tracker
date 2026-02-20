<?php

declare(strict_types=1);

namespace App\Features\Reports\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Enums\EventStatus;
use App\Enums\EventTrooperStatus;
use App\Enums\MembershipRole;
use App\Enums\MembershipStatus;
use App\Enums\OrganizationType;
use App\Models\AwardTrooper;
use App\Models\Event;
use App\Models\EventShift;
use App\Models\EventTrooper;
use App\Models\Notice;
use App\Models\Organization;
use App\Models\Trooper;
use App\Models\TrooperDonation;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Handler for the admin metrics dashboard.
 *
 * Optimized for selectable intervals: 30, 60, 90, 180, 360 days.
 *
 * @implements QueryHandlerInterface<GetDashboardMetricsQuery>
 */
readonly class GetDashboardMetricsQueryHandler implements QueryHandlerInterface
{
    /**
     * Build the full dashboard metrics payload for the given lookback period.
     *
     * @param  GetDashboardMetricsQuery  $message  The query containing lookback criteria.
     * @return array<string, mixed> Metrics grouped by section keys.
     */
    public function __invoke(object $message): array
    {
        $lookback = $message->parseLookback();

        return [
            'personnel' => $this->getPersonnelMetrics($lookback),
            'logistics' => $this->getLogisticsMetrics($lookback),
            'impact' => $this->getImpactMetrics($lookback),
            'engagement' => $this->getEngagementMetrics($lookback),
            'leaderboard' => $this->getOrganizationLeaderboard($lookback),
        ];
    }

    /**
     * Metrics regarding trooper recruitment and retention.
     *
     * @param  Carbon  $date  Start date for the lookback window.
     * @return array<string, int> Personnel metrics.
     */
    private function getPersonnelMetrics(Carbon $date): array
    {
        $active_count = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)->count();

        $handler_count = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->where(Trooper::MEMBERSHIP_ROLE, MembershipRole::HANDLER)
            ->count();

        $trooper_count = $active_count - $handler_count;

        $new_enlistments = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->where(Trooper::CREATED_AT, '>=', $date)
            ->count();

        $ghost_count = Trooper::where(Trooper::LAST_ACTIVE_AT, '<', $date)
            ->where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->count();

        $attendance_count = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->whereHas('event_troopers', function ($q) use ($date) {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->where(EventTrooper::SIGNED_UP_AT, '>=', $date);
            })
            ->count();

        $attrition_risk = Trooper::where(Trooper::MEMBERSHIP_STATUS, MembershipStatus::ACTIVE)
            ->whereDoesntHave('event_troopers', function ($q) use ($date) {
                $q->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED)
                    ->where(EventTrooper::SIGNED_UP_AT, '>=', $date);
            })
            ->count();

        return compact('active_count', 'trooper_count', 'handler_count', 'new_enlistments', 'ghost_count', 'attendance_count', 'attrition_risk');
    }

    /**
     * Metrics regarding event fulfillment and deployment efficiency.
     *
     * @param  Carbon  $date  Start date for the lookback window.
     * @return array<string, float> Logistics metrics.
     */
    private function getLogisticsMetrics(Carbon $date): array
    {
        $events = Event::with(['event_shifts.event_troopers'])
            ->where(Event::EVENT_START, '>=', $date)
            ->get();

        $total_events = $events->count();
        $fulfillment_rate = $total_events > 0
            ? ($events->where(Event::STATUS, EventStatus::CLOSED)->count() / $total_events) * 100
            : 0;

        $total_allowed = $events->flatMap->event_shifts->sum('troopers_allowed');
        $total_signed_up = $events->flatMap->event_shifts->flatMap->event_troopers->count();

        $shift_saturation = $total_allowed > 0 ? ($total_signed_up / $total_allowed) * 100 : 0;

        return compact('fulfillment_rate', 'shift_saturation');
    }

    /**
     * Credits, charity funds, and volunteer hours.
     *
     * @param  Carbon  $date  Start date for the lookback window.
     * @return array<string, int|float> Impact metrics.
     */
    private function getImpactMetrics(Carbon $date): array
    {
        $closed_events = Event::where(Event::EVENT_START, '>=', $date)
            ->where(Event::STATUS, EventStatus::CLOSED)
            ->get();

        $total_credits = $closed_events->sum(fn ($e) => $e->charity_direct_funds + $e->charity_indirect_funds);
        $volunteer_hours = $closed_events->sum('charity_hours');

        $internal_donations = TrooperDonation::where(TrooperDonation::CREATED_AT, '>=', $date)
            ->sum('amount');

        return compact('total_credits', 'volunteer_hours', 'internal_donations');
    }

    /**
     * Communication and system engagement (notices, awards, tags).
     *
     * @param  Carbon  $date  Start date for the lookback window.
     * @return array<string, int|float> Engagement metrics.
     */
    private function getEngagementMetrics(Carbon $date): array
    {
        $notices = Notice::withCount(['troopers', 'troopers as read_count' => function ($q) {
            $q->where('is_read', true);
        }])->where(Notice::STARTS_AT, '>=', $date)->get();

        $total_interactions = $notices->sum('troopers_count');
        $total_reads = $notices->sum('read_count');

        $notice_penetration = $total_interactions > 0 ? ($total_reads / $total_interactions) * 100 : 0;
        $award_velocity = AwardTrooper::where(AwardTrooper::CREATED_AT, '>=', $date)->count();
        $photo_activity = Event::whereHas('event_uploads', fn ($q) => $q->where(Event::CREATED_AT, '>=', $date))->count();

        return compact('notice_penetration', 'award_velocity', 'photo_activity');
    }

    /**
     * Comparative performance across organizations.
     *
     * @param  Carbon  $date  Start date for the lookback window.
     * @return Collection<int, array{name: string, events_completed: int, troopers_attended: int, direct_funds_raised: int|float, indirect_funds_raised: int|float, total_funds_raised: int|float}>
     */
    private function getOrganizationLeaderboard(Carbon $date): Collection
    {
        $org_fields = [Organization::ID, Organization::NAME];
        $event_fields = [
            Event::ID,
            Event::ORGANIZATION_ID,
            Event::PRIMARY_ORGANIZATION_ID,
            Event::CHARITY_DIRECT_FUNDS,
            Event::CHARITY_INDIRECT_FUNDS,
        ];
        $shift_fields = [EventShift::ID, EventShift::EVENT_ID];
        $trooper_fields = [EventTrooper::ID, EventTrooper::EVENT_SHIFT_ID, EventTrooper::TROOPER_ID];

        return Organization::select($org_fields)
            ->with([
                'events' => function ($q) use ($date, $event_fields, $trooper_fields) {
                    $q->select($event_fields)
                        ->where(Event::EVENT_START, '>=', $date)
                        ->where(Event::STATUS, EventStatus::CLOSED)
                        // Load the signups that are specifically in 'going' status
                        ->with([
                            'event_shifts.event_troopers' => function ($sq) use ($trooper_fields) {
                                $sq->select($trooper_fields)
                                    ->where(EventTrooper::STATUS, EventTrooperStatus::ATTENDED);
                            }]);
                }])
            ->where(Organization::TYPE, OrganizationType::ORGANIZATION)
            ->get()
            ->map(function ($org) {
                // 1. Flatten the events into their shifts
                // 2. Flatten those shifts into their troopers
                // 3. Pluck unique trooper IDs
                $attended_count = $org->events
                    ->flatMap(fn ($event) => $event->event_shifts)
                    ->flatMap(fn ($shift) => $shift->event_troopers)
                    ->pluck('trooper_id')
                    ->unique()
                    ->count();

                return [
                    'name' => $org->name,
                    'events_completed' => $org->events->count(),
                    'troopers_attended' => $attended_count,
                    'direct_funds_raised' => $org->events->sum(fn ($e) => $e->charity_direct_funds),
                    'indirect_funds_raised' => $org->events->sum(fn ($e) => $e->charity_indirect_funds),
                    'total_funds_raised' => $org->events->sum(fn ($e) => $e->charity_direct_funds + $e->charity_indirect_funds),
                ];
            })
            ->sortByDesc('total_funds_raised')
            ->take(10)
            ->values();
    }
}
