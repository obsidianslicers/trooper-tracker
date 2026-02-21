<?php

declare(strict_types=1);

namespace App\Features\Troopers\Queries;

use App\Bus\Contracts\QueryHandlerInterface;
use App\Models\Trooper;
use App\Models\EventTrooper;
use App\Models\TrooperDonation;
use Illuminate\Support\Facades\DB;

/**
 * Handler for retrieving troopers for picker/dropdown components.
 *
 * Processes GetTrooperServiceRecordQuery to return troopers based on:
 * - Organization filtering: Returns only troopers belonging to a specific organization
 * - Filter criteria: Applies search term and role filtering via TrooperFilter
 *
 * All results are ordered by trooper name for consistent UI display.
 *
 * @implements QueryHandlerInterface<GetTrooperServiceRecordQuery>
 */
readonly class GetTrooperServiceRecordQueryHandler implements QueryHandlerInterface
{
    /**
     * Handle the query to retrieve troopers for picker components.
     *
     * Query behavior:
     * 1. Start with active troopers who have completed setup, ordered by name
     * 2. If organization_id is set: Filter to troopers belonging to that organization
     * 3. If moderated_only is set: Filter to troopers moderated by the requesting trooper
     * 4. If filter has criteria: Apply search term and role filtering and return results
     * 5. If no filter criteria: Return empty collection
     *
     * @param  GetTrooperServiceRecordQuery  $message  The query containing filter and scope criteria
     * @return array Collection of filtered troopers, or empty if no filter applied
     */
    public function __invoke(object $message): mixed
    {
        return [
            'deployment_profile' => $this->getDeploymentProfile($message->trooper_id),
        ];
    }

    private function getDeploymentProfile(int $trooper_id): array
    {
        $trooper = Trooper::with('trooper_achievements')->findOrFail($trooper_id);

        // // Fetch Total Troops (Shifts) and Total Hours
        // // Using EventTrooper constants for table discipline
        // $stats = EventTrooper::where(EventTrooper::TROOPER_ID, $message->trooper_id)
        //     ->selectRaw('COUNT(*) as total_troops')
        //     ->selectRaw('SUM(COALESCE(travel_hours, 0) + COALESCE(troop_hours, 0)) as total_hours')
        //     ->first();

        // // Fetch Donation Totals
        // $donations = TrooperDonation::where(TrooperDonation::TROOPER_ID, $message->trooper_id)
        //     ->selectRaw('SUM(amount) as direct_funds')
        //     ->first();

        return [
            'trooper' => $trooper,
            // 'name' => $trooper->name,
            // 'rank_level' => $trooper->rank,
            // 'total_troops' => (int) ($stats->total_troops ?? 0),
            // 'total_hours' => (float) ($stats->total_hours ?? 0),
            // 'direct_funds' => (float) ($donations->direct_funds ?? 0),
            // 'member_since' => $trooper->created_at?->format('F Y') ?? 'Unknown',
            // 'status_label' => 'Active Duty', // Could be driven by Trooper::status enum
        ];
    }
}