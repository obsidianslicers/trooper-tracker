<?php

declare(strict_types=1);

namespace Database\Seeders\Issues\Support;

use App\Enums\MembershipStatus;
use App\Enums\TrooperRequestStatus;
use App\Models\EventTrooper;
use App\Models\Organization;
use App\Models\OrganizationCostume;
use App\Models\TrooperAssignment;
use App\Models\TrooperCostume;
use App\Models\TrooperOrganization;
use App\Models\TrooperRequest;
use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\Support\LegacyCredit;
use Database\Seeders\FloridaGarrison\Support\LegacySignupCreditResolver;
use Illuminate\Support\Facades\DB;

/**
 * Decides what troop credit a historical EventTrooper row should carry.
 *
 * Which rules apply depends on where the signup came from, never on the shift date:
 *
 *   - TT1.0 signup (a matching legacy event_sign_up row exists): credit comes only from that
 *     legacy signup's costume club. See LegacySignupCreditResolver.
 *   - TT2.0 signup (no legacy row): credit is inferred from what was true on the shift date —
 *     the costume's clubs (narrowed by the trooper's approvals) intersected with the clubs the
 *     trooper was a member of at that time. Handler / no costume credits every such club.
 *
 * Membership at a point in time is pieced together from all the evidence available: the TT1.0
 * pX flags (proof of membership at the TT2.0 launch, not forever after), membership join dates,
 * approved join requests, the earliest membership / assignment rows (trashed ones included, so a
 * root→region move keeps its original date) and soft-delete / retirement dates. A club joined
 * after a shift never credits it. When the evidence can't settle it, the answer is "unknown" and
 * the caller reports the row instead of guessing.
 *
 * Membership validity and credit validity are separate questions — nothing here changes a
 * membership.
 */
class HistoricalCreditResolver
{
    public const string MEMBER = 'member';

    public const string JOINED_LATER = 'joined_later';

    public const string ENDED = 'ended';

    public const string NO_EVIDENCE = 'no_evidence';

    public const string UNKNOWN = 'unknown';

    /** End of the TT2.0 launch day; TT1.0 pX flags prove membership up to here. */
    private const string LAUNCHED_AT = '2026-05-29 23:59:59';

    private const array CURRENT_STATUSES = [MembershipStatus::ACTIVE, MembershipStatus::RESERVE];

    private const array WAS_MEMBER_STATUSES = [
        MembershipStatus::ACTIVE,
        MembershipStatus::RESERVE,
        MembershipStatus::RETIRED,
        MembershipStatus::INACTIVE,
        MembershipStatus::DEPARTED,
    ];

    private Carbon $launched_at;

    /** @var array<int, int> organization id => root id */
    private array $root_ids = [];

    /** @var array<int, string> organization id => name */
    private array $org_names = [];

    /** @var array<int, array<int, Carbon>> [trooper_id][root_id] => earliest join evidence */
    private array $starts = [];

    /** @var array<int, array<int, Carbon>> [trooper_id][root_id] => latest end evidence */
    private array $ends = [];

    /** @var array<int, array<int, true>> [trooper_id][root_id] => currently a member */
    private array $current = [];

    /** @var array<int, array<int, array<int, int>>> [trooper_id][root_id] => current member org ids */
    private array $current_org_ids = [];

    /** @var array<int, array<int, int>> costume_id => root ids the costume belongs to */
    private array $costume_root_ids = [];

    /** @var array<int, array<int, array<int, int>>> [trooper_id][costume_id] => approved root ids */
    private array $approved_root_ids = [];

    /** @param  array<int, array<int, int>>  $excluded_roots  [trooper_id] => roots never to count as membership */
    private function __construct(
        private readonly LegacySignupCreditResolver $legacy,
        private readonly array $excluded_roots,
    ) {
        $this->launched_at = Carbon::parse(self::LAUNCHED_AT);
    }

    /** @param  array<int, array<int, int>>  $excluded_roots  [trooper_id] => known-false roots */
    public static function load(?LegacySignupCreditResolver $legacy = null, array $excluded_roots = []): self
    {
        $resolver = new self($legacy ?? LegacySignupCreditResolver::load(), $excluded_roots);
        $resolver->loadOrganizations();
        $resolver->loadMembershipRows();
        $resolver->loadAssignmentRows();
        $resolver->loadApprovedRequests();
        $resolver->loadCostumes();

        return $resolver;
    }

    public function legacy(): LegacySignupCreditResolver
    {
        return $this->legacy;
    }

    public function isTt1Signup(EventTrooper $event_trooper): bool
    {
        return $this->legacy->hasSignup($event_trooper->event_shift_id, $event_trooper->trooper_id);
    }

    public function legacyCredit(EventTrooper $event_trooper): LegacyCredit
    {
        return $this->legacy->resolve($event_trooper->event_shift_id, $event_trooper->trooper_id);
    }

    public function expectedCredit(EventTrooper $event_trooper): CreditResolution
    {
        if ($this->isTt1Signup($event_trooper))
        {
            $legacy = $this->legacyCredit($event_trooper);

            return $legacy->isResolved()
                ? CreditResolution::resolved($legacy->org_ids, $legacy->note)
                : CreditResolution::report($legacy->note);
        }

        $shift_date = $event_trooper->event_shift?->shift_starts_at;

        if ($shift_date === null)
        {
            return CreditResolution::report('Shift has no start time.');
        }

        return $this->inferTt2Credit($event_trooper, $shift_date);
    }

    /**
     * Splits stored credit into what the shift could have earned and what it couldn't. Never
     * adds anything.
     *
     * @param  array<int, int>|null  $org_ids  defaults to the row's costume_organization_ids
     */
    public function checkStoredCredit(EventTrooper $event_trooper, ?array $org_ids = null): CreditCheck
    {
        $org_ids ??= $event_trooper->costume_organization_ids ?? [];

        if ($this->isTt1Signup($event_trooper))
        {
            return $this->checkAgainstLegacy($event_trooper, $org_ids);
        }

        $shift_date = $event_trooper->event_shift?->shift_starts_at;

        if ($shift_date === null)
        {
            return new CreditCheck($org_ids, [], []);
        }

        return $this->checkAgainstMembership($event_trooper->trooper_id, $shift_date, $org_ids);
    }

    public function membershipStatusAt(int $trooper_id, int $root_id, Carbon $at): string
    {
        if (in_array($root_id, $this->excluded_roots[$trooper_id] ?? [], true))
        {
            return self::NO_EVIDENCE;
        }

        $member_at_launch = in_array($root_id, $this->legacy->launchMemberOrgIds($trooper_id), true);
        $start = $this->starts[$trooper_id][$root_id] ?? null;

        if (!$member_at_launch && $start === null)
        {
            return self::NO_EVIDENCE;
        }

        if (!$member_at_launch && $at->lt($start))
        {
            return self::JOINED_LATER;
        }

        if ($member_at_launch && $at->lte($this->launched_at))
        {
            return self::MEMBER;
        }

        return $this->continuedMembershipAt($trooper_id, $root_id, $at);
    }

    /**
     * Keeps stored ids that already sit under one of the target clubs (a region/unit id is more
     * specific than the bare root) and adds the root for any target club not yet covered.
     *
     * @param  array<int, int>  $current_ids
     * @param  array<int, int>  $target_ids
     * @return array<int, int>
     */
    public function mergeKeepingSpecificity(array $current_ids, array $target_ids): array
    {
        $target_roots = $this->rootsOf($target_ids);

        $kept = collect($current_ids)
            ->filter(fn (int $id) => in_array($this->rootOf($id), $target_roots, true))
            ->values();

        $kept_roots = $this->rootsOf($kept->all());

        $missing = collect($target_ids)
            ->reject(fn (int $id) => in_array($this->rootOf($id), $kept_roots, true));

        return $kept->merge($missing)->unique()->values()->all();
    }

    public function rootOf(int $org_id): ?int
    {
        return $this->root_ids[$org_id] ?? null;
    }

    /**
     * @param  array<int, int>  $org_ids
     * @return array<int, int>
     */
    public function rootsOf(array $org_ids): array
    {
        return collect($org_ids)
            ->map(fn (int $id) => $this->rootOf($id))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /** @param  array<int, int>  $org_ids */
    public function namesOf(array $org_ids): string
    {
        return collect($org_ids)->map(fn (int $id) => $this->org_names[$id] ?? "#{$id}")->implode(', ');
    }

    /**
     * @param  array<int, int>  $a
     * @param  array<int, int>  $b
     */
    public static function sameIds(array $a, array $b): bool
    {
        $normalize = fn (array $ids) => collect($ids)->map(fn ($id) => (int) $id)->unique()->sort()->values()->all();

        return $normalize($a) === $normalize($b);
    }

    private function inferTt2Credit(EventTrooper $event_trooper, Carbon $shift_date): CreditResolution
    {
        $trooper_id = $event_trooper->trooper_id;
        $costume = $event_trooper->costume;
        $by_handler_rule = $costume === null || $costume->countsAsHandler();

        $candidates = $by_handler_rule
            ? $this->knownRoots($trooper_id)
            : $this->costumeRoots($trooper_id, $costume->id);

        if (!$by_handler_rule && empty($candidates))
        {
            return CreditResolution::report("Costume \"{$costume->name}\" isn't associated with any club.");
        }

        $statuses = collect($candidates)->mapWithKeys(fn (int $root) => [
            $root => $this->membershipStatusAt($trooper_id, $root, $shift_date),
        ]);

        $member_roots = $statuses->filter(fn (string $status) => $status === self::MEMBER)->keys()->all();

        if (!empty($member_roots))
        {
            return CreditResolution::resolved(
                $this->specificOrgIds($trooper_id, $member_roots),
                'Member of '.$this->namesOf($member_roots).' on the shift date.',
            );
        }

        $unknown_roots = $statuses->filter(fn (string $status) => $status === self::UNKNOWN)->keys()->all();

        if (!empty($unknown_roots))
        {
            return CreditResolution::report(
                'Membership in '.$this->namesOf($unknown_roots)." on the shift date can't be established."
            );
        }

        return CreditResolution::report($by_handler_rule
            ? 'Trooper had no club membership on the shift date.'
            : "Trooper wasn't a member of any club for costume \"{$costume->name}\" on the shift date.");
    }

    /** @param  array<int, int>  $org_ids */
    private function checkAgainstLegacy(EventTrooper $event_trooper, array $org_ids): CreditCheck
    {
        $legacy = $this->legacyCredit($event_trooper);

        if (!$legacy->isResolved())
        {
            return new CreditCheck($org_ids, [], [], $legacy->note);
        }

        $legacy_roots = $this->rootsOf($legacy->org_ids);
        [$keep, $remove] = collect($org_ids)
            ->partition(fn (int $id) => in_array($this->rootOf($id), $legacy_roots, true));

        return new CreditCheck($keep->values()->all(), $remove->values()->all(), []);
    }

    /** @param  array<int, int>  $org_ids */
    private function checkAgainstMembership(int $trooper_id, Carbon $shift_date, array $org_ids): CreditCheck
    {
        $keep = [];
        $remove = [];
        $unknown = [];

        foreach ($org_ids as $org_id)
        {
            $root = $this->rootOf((int) $org_id);
            $status = $root === null ? self::MEMBER : $this->membershipStatusAt($trooper_id, $root, $shift_date);

            if ($status === self::JOINED_LATER || $status === self::ENDED)
            {
                $remove[] = (int) $org_id;

                continue;
            }

            $keep[] = (int) $org_id;

            if ($status === self::NO_EVIDENCE || $status === self::UNKNOWN)
            {
                $unknown[] = $root;
            }
        }

        return new CreditCheck($keep, $remove, array_values(array_unique($unknown)));
    }

    private function continuedMembershipAt(int $trooper_id, int $root_id, Carbon $at): string
    {
        if (isset($this->current[$trooper_id][$root_id]))
        {
            return self::MEMBER;
        }

        $end = $this->ends[$trooper_id][$root_id] ?? null;

        if ($end === null)
        {
            return self::UNKNOWN;
        }

        return $at->lte($end) ? self::MEMBER : self::ENDED;
    }

    /** @return array<int, int> */
    private function knownRoots(int $trooper_id): array
    {
        return collect($this->legacy->launchMemberOrgIds($trooper_id))
            ->map(fn (int $id) => $this->rootOf($id) ?? $id)
            ->merge(array_keys($this->starts[$trooper_id] ?? []))
            ->unique()
            ->values()
            ->all();
    }

    /** @return array<int, int> */
    private function costumeRoots(int $trooper_id, int $costume_id): array
    {
        return $this->approved_root_ids[$trooper_id][$costume_id] ?? $this->costume_root_ids[$costume_id] ?? [];
    }

    /**
     * A trooper's current region/unit assignment under a credited club, when there is one —
     * matches what live attendance confirmation stores. Falls back to the bare root.
     *
     * @param  array<int, int>  $root_ids
     * @return array<int, int>
     */
    private function specificOrgIds(int $trooper_id, array $root_ids): array
    {
        return collect($root_ids)
            ->flatMap(fn (int $root) => $this->current_org_ids[$trooper_id][$root] ?? [$root])
            ->unique()
            ->values()
            ->all();
    }

    private function loadOrganizations(): void
    {
        foreach (Organization::withTrashed()->get([Organization::ID, Organization::NAME, Organization::NODE_PATH]) as $org)
        {
            $this->root_ids[$org->id] = Organization::rootIdFromPath((string) $org->node_path) ?: $org->id;
            $this->org_names[$org->id] = $org->name;
        }
    }

    private function loadMembershipRows(): void
    {
        $rows = TrooperOrganization::withTrashed()->get([
            TrooperOrganization::TROOPER_ID,
            TrooperOrganization::ORGANIZATION_ID,
            TrooperOrganization::IDENTIFIER,
            TrooperOrganization::MEMBERSHIP_STATUS,
            TrooperOrganization::JOIN_DATE,
            TrooperOrganization::CREATED_AT,
            TrooperOrganization::UPDATED_AT,
            TrooperOrganization::DELETED_AT,
        ]);

        foreach ($rows as $row)
        {
            $root = $this->rootOf($row->organization_id);

            if ($root === null || $this->isFalseImportRow($row))
            {
                continue;
            }

            $this->recordMembershipRow($row, $root);
        }
    }

    private function recordMembershipRow(TrooperOrganization $row, int $root): void
    {
        $was_member = in_array($row->membership_status, self::WAS_MEMBER_STATUSES, true);

        // One row per (trooper, club) — a real later join reuses the row the old import created
        // from a stray identifier, so its created_at is the import day, not a join date.
        $created_at_is_evidence = $this->legacy->strayIdentifier($row->trooper_id, $row->organization_id) === null;

        if ($was_member)
        {
            $evidence = [$row->join_date, $created_at_is_evidence ? $row->created_at : null];
            $this->recordStart($row->trooper_id, $root, collect($evidence)->filter()->min());
        }

        if ($row->deleted_at !== null)
        {
            if ($was_member)
            {
                $this->recordEnd($row->trooper_id, $root, $row->deleted_at);
            }

            return;
        }

        if (in_array($row->membership_status, self::CURRENT_STATUSES, true))
        {
            $this->current[$row->trooper_id][$root] = true;
        }
        elseif ($was_member)
        {
            $this->recordEnd($row->trooper_id, $root, $row->updated_at);
        }
    }

    /**
     * A membership the old importer created from a stray legacy identifier (permission flag 0)
     * that has since been flagged — retired/reserve or soft-deleted, the same pairs Fix408
     * corrects. It was never a real membership, so it proves nothing about join dates.
     */
    private function isFalseImportRow(TrooperOrganization $row): bool
    {
        $stray = $this->legacy->strayIdentifier($row->trooper_id, $row->organization_id);

        if ($stray === null || (string) $row->identifier !== $stray)
        {
            return false;
        }

        return $row->deleted_at !== null
            || in_array($row->membership_status, [MembershipStatus::RETIRED, MembershipStatus::RESERVE], true);
    }

    private function loadAssignmentRows(): void
    {
        $rows = DB::table('tt_trooper_assignments')
            ->where(TrooperAssignment::IS_MEMBER, true)
            ->get([
                TrooperAssignment::TROOPER_ID,
                TrooperAssignment::ORGANIZATION_ID,
                TrooperAssignment::CREATED_AT,
                TrooperAssignment::DELETED_AT,
            ]);

        foreach ($rows as $row)
        {
            $root = $this->rootOf((int) $row->organization_id);

            if ($root === null || $row->created_at === null)
            {
                continue;
            }

            $trooper_id = (int) $row->trooper_id;
            $this->recordStart($trooper_id, $root, Carbon::parse($row->created_at));

            if ($row->deleted_at !== null)
            {
                $this->recordEnd($trooper_id, $root, Carbon::parse($row->deleted_at));

                continue;
            }

            $this->current[$trooper_id][$root] = true;
            $this->current_org_ids[$trooper_id][$root][] = (int) $row->organization_id;
        }
    }

    private function loadApprovedRequests(): void
    {
        $rows = TrooperRequest::query()
            ->where(TrooperRequest::STATUS, TrooperRequestStatus::APPROVED->value)
            ->get([TrooperRequest::TROOPER_ID, TrooperRequest::ORGANIZATION_ID, TrooperRequest::UPDATED_AT]);

        foreach ($rows as $row)
        {
            $root = $this->rootOf($row->organization_id);

            if ($root !== null && $row->updated_at !== null)
            {
                $this->recordStart($row->trooper_id, $root, $row->updated_at);
            }
        }
    }

    private function loadCostumes(): void
    {
        foreach (OrganizationCostume::query()->get([OrganizationCostume::ORGANIZATION_ID, OrganizationCostume::COSTUME_ID]) as $row)
        {
            $root = $this->rootOf($row->organization_id);

            if ($root !== null && !in_array($root, $this->costume_root_ids[$row->costume_id] ?? [], true))
            {
                $this->costume_root_ids[$row->costume_id][] = $root;
            }
        }

        $approvals = DB::table('tt_trooper_costumes')
            ->join('tt_organization_costumes', 'tt_organization_costumes.id', '=', 'tt_trooper_costumes.organization_costume_id')
            ->whereNull('tt_trooper_costumes.'.TrooperCostume::DELETED_AT)
            ->whereNull('tt_organization_costumes.'.OrganizationCostume::DELETED_AT)
            ->get(['tt_trooper_costumes.trooper_id', 'tt_organization_costumes.costume_id', 'tt_organization_costumes.organization_id']);

        foreach ($approvals as $approval)
        {
            $root = $this->rootOf((int) $approval->organization_id);
            $existing = $this->approved_root_ids[(int) $approval->trooper_id][(int) $approval->costume_id] ?? [];

            if ($root !== null && !in_array($root, $existing, true))
            {
                $this->approved_root_ids[(int) $approval->trooper_id][(int) $approval->costume_id][] = $root;
            }
        }
    }

    private function recordStart(int $trooper_id, int $root, ?Carbon $date): void
    {
        if ($date === null)
        {
            return;
        }

        $existing = $this->starts[$trooper_id][$root] ?? null;

        if ($existing === null || $date->lt($existing))
        {
            $this->starts[$trooper_id][$root] = $date->copy();
        }
    }

    private function recordEnd(int $trooper_id, int $root, ?Carbon $date): void
    {
        if ($date === null)
        {
            return;
        }

        $existing = $this->ends[$trooper_id][$root] ?? null;

        if ($existing === null || $date->gt($existing))
        {
            $this->ends[$trooper_id][$root] = $date->copy();
        }
    }
}
