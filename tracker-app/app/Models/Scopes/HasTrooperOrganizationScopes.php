<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Enums\MembershipStatus;
use App\Models\Trooper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Trait containing local scopes for the TrooperOrganization model.
 *
 * This trait provides query scopes for filtering and retrieving trooper-organization
 * pivot relationships, including membership status and verification states.
 */
trait HasTrooperOrganizationScopes
{
    public function scopePending(Builder $query): Builder
    {
        return $query->where(self::MEMBERSHIP_STATUS, MembershipStatus::PENDING);
    }

    public function scopeForModerator(Builder $query, Trooper $moderator): Builder
    {
        if ($moderator->is_administrator)
        {
            return $query;
        }

        return $query->whereExists(function ($sub) use ($moderator)
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_mod')
                ->join('tt_organizations as org_mod', 'ta_mod.organization_id', '=', 'org_mod.id')
                ->join('tt_organizations as org_req', 'org_req.id', '=', 'tt_trooper_organizations.organization_id')
                ->where('ta_mod.trooper_id', $moderator->id)
                ->where('ta_mod.is_moderator', true)
                ->whereRaw('org_req.node_path LIKE CONCAT(org_mod.node_path, "%")');
        });
    }
}