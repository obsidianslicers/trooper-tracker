<?php

declare(strict_types=1);

namespace Database\Seeders\FloridaGarrison\Support;

use Carbon\Carbon;
use Database\Seeders\FloridaGarrison\Traits\HasClubMaps;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Reads a TT1.0 signup's club credit from its own legacy record only (event_sign_up.costume →
 * costumes.club) — never membership or the pX flags; unmappable clubs are "unknown", not guessed.
 * Shared by EventSeeder and the Issues fixes so the import and the repair always agree.
 */
class LegacySignupCreditResolver
{
    use HasClubMaps;

    private const array LEGACY_TABLES = ['troopers', 'events', 'event_sign_up', 'costumes'];

    private bool $available = false;

    /** @var array<int, int> legacy costume club code => organization id */
    private array $club_org_ids = [];

    /** @var array<int, array{name: string, club: ?int}> */
    private array $costumes = [];

    /** @var array<string, int> "shift_id:trooper_id" => first legacy costume id */
    private array $signups = [];

    /** @var array<string, array<int, int>> "shift_id:trooper_id" => further costume ids */
    private array $extra_signup_costumes = [];

    /** @var array<int, array<int, int>> trooper_id => organization ids with a pX flag >= 1 */
    private array $launch_member_org_ids = [];

    /** @var array<int, array<int, string>> trooper_id => [organization_id => stray identifier] */
    private array $stray_identifiers = [];

    public static function load(): self
    {
        $resolver = new self;
        $resolver->boot();

        return $resolver;
    }

    public static function hasLegacyTables(): bool
    {
        foreach (self::LEGACY_TABLES as $table)
        {
            if (!Schema::hasTable($table))
            {
                return false;
            }
        }

        return true;
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function hasSignup(int $shift_id, int $trooper_id): bool
    {
        return isset($this->signups["{$shift_id}:{$trooper_id}"]);
    }

    public function resolve(int $shift_id, int $trooper_id): LegacyCredit
    {
        $key = "{$shift_id}:{$trooper_id}";

        if (!isset($this->signups[$key]))
        {
            return LegacyCredit::missing();
        }

        $costume_ids = array_values(array_unique([
            $this->signups[$key],
            ...($this->extra_signup_costumes[$key] ?? []),
        ]));
        $credits = array_map(fn (int $id) => $this->creditForCostume($id), $costume_ids);

        $distinct = collect($credits)
            ->map(fn (LegacyCredit $credit) => $this->fingerprint($credit))
            ->unique();

        return $distinct->count() > 1 ? $this->disagreement($costume_ids) : $credits[0];
    }

    private function fingerprint(LegacyCredit $credit): string
    {
        return $credit->status.':'.implode(',', $this->sorted($credit->org_ids));
    }

    /** @param  array<int, int>  $costume_ids */
    private function disagreement(array $costume_ids): LegacyCredit
    {
        $names = collect($costume_ids)
            ->map(fn (int $id) => $this->costumes[$id]['name'] ?? "#{$id}")
            ->implode('", "');

        return LegacyCredit::ambiguous("Legacy signups for this shift disagree (\"{$names}\").");
    }

    /**
     * The club(s) a single legacy costume was tagged with. Dual/triple tags credit every club on
     * the tag.
     */
    public function creditForCostume(int $legacy_costume_id): LegacyCredit
    {
        $costume = $this->costumes[$legacy_costume_id] ?? null;

        if ($costume === null)
        {
            return LegacyCredit::unmapped('Legacy signup has no costume on record.');
        }

        $name = $costume['name'];

        if ($costume['club'] === null)
        {
            return LegacyCredit::unmapped("Legacy costume \"{$name}\" has no club on record.");
        }

        $org_ids = $this->orgIdsForClub($costume['club']);

        if (empty($org_ids))
        {
            return LegacyCredit::unmapped(
                "Legacy costume \"{$name}\" (club {$costume['club']}) doesn't map to a club."
            );
        }

        return LegacyCredit::resolved($org_ids, "Legacy costume \"{$name}\".");
    }

    /** @return array<int, int> */
    private function orgIdsForClub(int $club): array
    {
        return collect($this->expandDualClubIds([$club]))
            ->map(fn (int $club_id) => $this->club_org_ids[$club_id] ?? null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Clubs whose TT1.0 permission flag was >= 1 — membership as of the TT2.0 launch.
     *
     * @return array<int, int>
     */
    public function launchMemberOrgIds(int $trooper_id): array
    {
        return $this->launch_member_org_ids[$trooper_id] ?? [];
    }

    /**
     * The legacy identifier a trooper had for a club whose permission flag was 0. The old import
     * turned exactly these into false memberships.
     */
    public function strayIdentifier(int $trooper_id, int $organization_id): ?string
    {
        return $this->stray_identifiers[$trooper_id][$organization_id] ?? null;
    }

    private function boot(): void
    {
        if (!self::hasLegacyTables())
        {
            return;
        }

        try
        {
            $costume_club_map = $this->getCostumeClubMap();
            $organization_club_map = $this->getOrganizationClubMap();
        }
        catch (Exception)
        {
            // named clubs like "501st Legion" don't exist here, nothing to map against
            return;
        }

        foreach ($costume_club_map as $club)
        {
            $this->club_org_ids[$club['costume_club_id']] = $club['id'];
        }

        $this->loadCostumes();
        $this->loadSignups($this->buildLegacyShiftMap());
        $this->loadLaunchMemberships($organization_club_map);

        $this->available = true;
    }

    private function loadCostumes(): void
    {
        foreach (DB::table('costumes')->get(['id', 'costume', 'club']) as $costume)
        {
            $this->costumes[(int) $costume->id] = [
                'name' => trim((string) $costume->costume),
                'club' => $costume->club === null ? null : (int) $costume->club,
            ];
        }
    }

    /**
     * Mirrors EventSeeder::overlayShifts(): linked legacy events become shifts of their main
     * event, and shifts of one event that start the same minute are merged into the first one.
     * Legacy signups on a merged shift therefore live on a different tt shift id.
     *
     * @return array<int, int> legacy shift id => tt shift id
     */
    private function buildLegacyShiftMap(): array
    {
        $map = [];

        foreach ($this->groupLegacyEvents() as $shifts)
        {
            $first_by_start = [];

            foreach ($shifts as $shift)
            {
                $key = Carbon::parse($shift->dateStart)->format('Y-m-d H:i');
                $first_by_start[$key] ??= (int) $shift->id;
                $map[(int) $shift->id] = $first_by_start[$key];
            }
        }

        return $map;
    }

    /** @return array<int, array<int, object>> main legacy event id => its shifts, main first */
    private function groupLegacyEvents(): array
    {
        $events = DB::table('events')
            ->orderBy('id')
            ->orderBy('link')
            ->get(['id', 'link', 'dateStart']);
        $groups = [];

        foreach ($events->filter(fn ($event) => (int) $event->link === 0) as $event)
        {
            $groups[(int) $event->id] = [$event];
        }

        foreach ($events->filter(fn ($event) => (int) $event->link > 0) as $event)
        {
            $groups[(int) $event->link][] = $event;
        }

        return $groups;
    }

    /** @param  array<int, int>  $shift_map */
    private function loadSignups(array $shift_map): void
    {
        $signups = DB::table('event_sign_up')
            ->orderBy('id')
            ->select(['trooperid', 'troopid', 'costume'])
            ->cursor();

        foreach ($signups as $signup)
        {
            $shift_id = $shift_map[(int) $signup->troopid] ?? (int) $signup->troopid;
            $key = "{$shift_id}:{$signup->trooperid}";
            $costume_id = (int) $signup->costume;

            if (!isset($this->signups[$key]))
            {
                $this->signups[$key] = $costume_id;
            }
            elseif ($this->signups[$key] !== $costume_id)
            {
                $this->extra_signup_costumes[$key][] = $costume_id;
            }
        }
    }

    /**
     * @param  array<string, array{id: int, identity: string, permission_column: string}>  $club_map
     */
    private function loadLaunchMemberships(array $club_map): void
    {
        foreach (DB::table('troopers')->cursor() as $trooper)
        {
            foreach ($club_map as $club)
            {
                $this->recordLaunchMembership($trooper, $club);
            }
        }
    }

    /** @param  array{id: int, identity: string, permission_column: string}  $club */
    private function recordLaunchMembership(object $trooper, array $club): void
    {
        $trooper_id = (int) $trooper->id;

        if ((int) ($trooper->{$club['permission_column']} ?? 0) >= 1)
        {
            $this->launch_member_org_ids[$trooper_id][] = $club['id'];

            return;
        }

        $identifier = $club['identity'] !== '' ? ($trooper->{$club['identity']} ?? null) : null;

        if ($identifier !== null && $identifier !== '' && $identifier !== '0')
        {
            $this->stray_identifiers[$trooper_id][$club['id']] = (string) $identifier;
        }
    }

    /**
     * @param  array<int, int>  $ids
     * @return array<int, int>
     */
    private function sorted(array $ids): array
    {
        sort($ids);

        return $ids;
    }
}
