<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Models\Trooper;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The review scope shared by costume approvals and troop verification: a moderator
 * qualifies when one unit they moderate covers one of the trooper's memberships and
 * sits in the same club tree as the club being reviewed.
 */
final class ModeratorCoverage
{
    /**
     * @param  int|string  $trooper  A trooper id, or the column that holds it.
     * @param  string  $organization_id_column  The column holding the club under review.
     */
    public static function apply(
        Builder $query,
        Trooper $moderator,
        int|string $trooper,
        string $organization_id_column,
    ): Builder {
        return $query->whereExists(function ($sub) use ($moderator, $trooper, $organization_id_column): void
        {
            $sub->select(DB::raw(1))
                ->from('tt_trooper_assignments as ta_moderator')
                ->join('tt_organizations as org_moderator', 'ta_moderator.organization_id', '=', 'org_moderator.id')
                ->join('tt_organizations as org_review', 'org_review.id', '=', DB::raw($organization_id_column))
                ->where('ta_moderator.trooper_id', $moderator->id)
                ->where('ta_moderator.is_moderator', true)
                ->whereNull('ta_moderator.deleted_at')
                ->where(function ($tree): void
                {
                    $tree->whereRaw('org_moderator.node_path LIKE CONCAT(org_review.node_path, "%")')
                        ->orWhereRaw('org_review.node_path LIKE CONCAT(org_moderator.node_path, "%")');
                })
                ->whereExists(fn ($member) => self::memberOfModeratedUnit($member, $trooper));
        });
    }

    private static function memberOfModeratedUnit(Builder $member, int|string $trooper): void
    {
        $member->select(DB::raw(1))
            ->from('tt_trooper_assignments as ta_member')
            ->join('tt_organizations as org_member', 'ta_member.organization_id', '=', 'org_member.id')
            ->where('ta_member.is_member', true)
            ->whereNull('ta_member.deleted_at')
            ->whereRaw('org_member.node_path LIKE CONCAT(org_moderator.node_path, "%")');

        is_int($trooper)
            ? $member->where('ta_member.trooper_id', $trooper)
            : $member->whereColumn('ta_member.trooper_id', $trooper);
    }
}
