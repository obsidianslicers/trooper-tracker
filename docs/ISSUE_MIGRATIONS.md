# Issue Fix Seeders

Issue fixes are one-time data repair seeders stored in `database/seeders/Issues/`. They correct bad data introduced by bugs, migration gaps, or legacy import problems. They are **not** run automatically — each must be executed manually.

```bash
php artisan db:seed --class="Database\Seeders\Issues\Fix<number>"
```

All fixes are idempotent (safe to run more than once) unless noted otherwise.

---

## Fix197

**Issue:** Missing test trooper accounts for guardian and minor-member flows.

**What it does:** Creates three seeded test accounts used for local development of visitor/guardian features:

- `visitor@sw.com` — Visitor-role trooper with a pending 501st membership and a guardian link
- `guardian@sw.com` — Active member who acts as the guardian
- `child@sw.com` — Pending minor member assigned to the Galactic Academy (Florida Dagobah School unit)

These accounts are idempotent — re-running the seeder updates existing records rather than duplicating them.

**When to run:** On any environment that needs to test the guardian/minor-member approval flow locally.

---

## Fix208

**Issue:** A placeholder trooper account (default ID `1206`) was used to hold roster spots before the `EventGuest` model existed. After `EventGuest` was introduced, those sign-ups remained as `EventTrooper` records tied to a fake trooper rather than as proper guest entries.

**What it does:** Converts all `EventTrooper` records belonging to the placeholder trooper into `EventGuest` records, then soft-deletes the placeholder trooper. Attendance statuses are mapped: cancelled/no-show/unable states become `CANCELLED`; everything else becomes `GOING`. Duplicate guest names on the same shift are disambiguated with a numeric suffix (`Placeholder`, `Placeholder 2`, etc.).

The placeholder trooper ID defaults to `1206` but is prompted interactively when run via the CLI.

**When to run:** Once, against the production database, after deploying the `EventGuest` feature. The placeholder trooper ID must exist or the seeder exits with a warning.

---

## Fix242

**Issue:** Several membership-data integrity problems accumulated from legacy code that stored join requests as `TrooperOrganization` rows (instead of the newer `TrooperRequest` table), and from a missing observer that allowed `TrooperAssignment` flags and soft-deletes to fall out of sync.

**What it does (seven distinct repairs):**

1. **Pending join requests** — Migrates `TrooperOrganization` rows with `pending` status into `TrooperRequest` records, then soft-deletes the old rows.
2. **Denied join requests** — Same as above for `denied` status. Denial reason is left null (was never stored in the old model).
3. **Sub-org memberships** — `TrooperOrganization` rows that pointed to a region or unit instead of the primary club are repaired: a correct primary-club membership is upserted, the assignment is moved to the sub-org, and the bad row is soft-deleted.
4. **Denied troopers with pending requests** — Any `TrooperRequest` that is still `pending` for a trooper whose account status is `denied` is updated to `denied`.
5. **is_member flag not cleared on delete** — `TrooperAssignment` rows that were soft-deleted but never had `is_member` set to `false` are corrected.
6. **Flag cleared but row not deleted** — `TrooperAssignment` rows where `is_member = false`, `is_moderator = false`, `should_notify = false`, and `deleted_at IS NULL` are soft-deleted.
7. **Hierarchy duplicates** — Where a trooper has multiple active `is_member = true` assignments within the same primary-club hierarchy, all but the most recently updated are soft-deleted.
8. **Orphaned primary-club memberships** — `TrooperOrganization` rows at the primary-club level with no active `TrooperAssignment` anywhere in that hierarchy get a missing assignment created.

**When to run:** Once, against any environment that was running before the `TrooperRequest` table and membership observer were introduced.

---

## Fix246

**Issue:** The v1.0 → v2.0 data migration copied charity data from the old `events` table onto `tt_events`, but not onto individual `tt_event_shifts`. Charity information was silently lost for events that had per-shift charity values.

**What it does:** Reads the legacy `events` table (if still present in the database) and backfills `tt_event_shifts` with the original charity fields: `charity_direct_funds`, `charity_indirect_funds`, `charity_name`, `charity_hours`, and `charity_notes`. The `charityAddHours` offset is added to the shift duration to compute the correct `charity_hours` value.

Shifts with no matching legacy row or with empty charity data are skipped. Outputs a summary of scanned, matched, and updated shift counts.

**When to run:** Once, on any environment that was imported from v1.0 data and still has the legacy `events` table present. Safe to skip on fresh v2.0 installs.

---

## Fix253

**Issue:** Missing test event and trooper needed to reproduce and verify a specific bug scenario locally.

**What it does:** Creates a test event (`Test Event for Fix253`) starting one hour ago with a single shift, and a test trooper account (`fix253@sw.com`). Both are upserted — re-running updates existing records.

**When to run:** On any local or staging environment where the Fix253 scenario needs to be reproduced.

---

## Fix287

**Issue:** When a trooper changed their costume via the self-service HTMX dropdown on the event page, only `costume_id` was saved — `costume_organization_ids` was never recalculated. If the trooper had previously selected a different costume (e.g., an RL costume that set RL org IDs), those stale org IDs persisted after the costume change. At attendance confirmation the stale IDs were used as-is, crediting the wrong club — e.g., Rebel Legion getting troop credit for a Stormtrooper (501st) costume.

A secondary gap in `EventTrooper::getEligibleCreditOrganizations()` compounded this: when `costume_id` was set but `costume_organization_ids` was null, the method fell through to returning all of the trooper's member organizations rather than the costume's actual approved orgs.

**What it does:** Scans all `EventTrooper` records where `costume_id IS NOT NULL` and the costume is not Handler or Command Staff (which derive credit from membership, not costume approvals). For each record it computes the correct org IDs via `Costume::approvedOrgIdsForTrooper()` and repairs in two cases:

- **Null org IDs** — `costume_organization_ids` is null and approved IDs are available → populate from costume approvals.
- **Stale org IDs** — `costume_organization_ids` contains IDs not in the approved list → filter to the valid intersection; if the intersection is empty, replace entirely with the approved IDs.

Records where `approvedOrgIdsForTrooper` returns empty are skipped (the costume approval may have been removed and requires manual review). Outputs counts for each category.

**When to run:** Once, against any environment running before the HTMX costume-update fix (the forward fix ships alongside this seeder). Run on production before reloading any affected service record pages.

---

## Fix394

**Issue:** Troopers without a valid email address cannot be contacted or recover access to their
account, and should not be counted as active members.

**What it does:** Finds every trooper whose email fails `Trooper::emailAppearsValid()` (missing or
not a real email address) and sets their global `membership_status` to `retired`. For each of those
troopers, also retires every non-deleted `TrooperOrganization` membership row (sets
`membership_status` to `retired` in every organization they belong to).

**When to run:** Once, against any environment where troopers with invalid/missing emails have
accumulated. Safe to re-run — already-retired records are excluded from both updates.

---

## Fix406–Fix409: historical troop credit

These four fixes repair troop credit (`tt_event_troopers.costume_organization_ids`) and the
memberships and achievements built on it. They share one set of credit rules —
`HistoricalCreditResolver` (`database/seeders/Issues/Support/`) on top of
`LegacySignupCreditResolver` (`database/seeders/FloridaGarrison/Support/`), which the original
`EventSeeder` import also uses, so a fresh import and a repaired database agree.

**Background — how the bad credit got there:**

- The admin roster-update controller (before #262) cleared credit on every save, and the import
  never credited handler-role troopers.
- The old `Fix406`/`Fix407` back-filled those rows from the trooper's *current* clubs, so a trooper
  who joined a club in 2026 got credit for it on shifts from 2017 (trooper 644).
- The old importer created memberships from a stray legacy identifier alone (Fix408), and the old
  `Fix409` guessed join dates from `created_at`, treating those false import rows as proof of
  TT1.0 membership.

### Credit rules

Which rules apply is decided **per signup, never by the shift or event date** — TT2.0 launched
2026-05-29, and an event scheduled before launch can hold both TT1.0 signups and later TT2.0
signups.

**TT1.0 signup** — a matching legacy `event_sign_up` row exists for the (shift, trooper), followed
through the same duplicate-shift merge `EventSeeder` performs. Credit comes **only** from that
signup's costume club (`event_sign_up.costume` → `costumes.club`); dual/triple tags credit every
club on the tag. Handler, N/A and Command Staff were per-club costumes in TT1.0, so nearly every
signup names a club. Nothing else is consulted — not TT2.0 membership, join dates, current
eligibility, or the legacy `pX` flags. If the costume's club can't be mapped (club 4 "Other", no
costume) or duplicate legacy signups disagree, the row is reported, not guessed.

**TT2.0 signup** — no legacy signup. Credit is inferred from what was true on the shift date:

- *Membership at time T* for a club holds when either:
  - the legacy `pX >= 1` — proof of membership **at launch**; it covers T up to launch, and after
    launch only while TT2.0 history shows the membership continuing (not retired / soft-deleted
    before T), or
  - the earliest reliable join evidence is on or before T: `join_date`, an approved
    `TrooperRequest`, the earliest `tt_trooper_organizations.created_at`, or the earliest
    `is_member` assignment (trashed rows included, so a root→region move keeps its original
    date). Region/unit assignments roll up to their club.

  A `tt_trooper_organizations` row for a club where TT1.0 had a stray identifier (`pX = 0`) never
  counts by `created_at` — the import created it, and a real later join reuses the same row.
- *Costume*: the clubs the costume belongs to, narrowed by the trooper's approvals. Credit every
  one of those clubs the trooper was a member of at T. A handler / no costume credits every club
  they were a member of at T.
- A club joined after T never credits T. Credit for a club with no membership evidence either way
  is kept and reported, never removed.

Membership validity and credit validity are separate questions: credit logic never changes a
membership, and a questionable membership never decides TT1.0 credit.

### Fix406 — restore missing credit

Scans `ATTENDED` rows with `organization_id IS NULL` and no `costume_organization_ids` (null or
`[]` — matched with `whereJsonLength`, since MySQL never compares a bound `'[]'` as JSON) and sets
the credit the rules above produce. Rows the evidence can't settle stay uncredited and go to
administrators in `Fix406OutstandingCredit`, with a reason per row.

### Fix407 — TT1.0 signups match their legacy signup

For every `ATTENDED` TT1.0 signup, makes the credited clubs equal the legacy signup's club(s):
stored region/unit ids under those clubs are kept, missing clubs added, others dropped. Rows whose
legacy signup can't be mapped keep their credit and are reported (`Fix407OutstandingCredit`);
uncredited ones are left to Fix406, which reports them itself.

### Fix408 — false memberships (conservative)

`TrooperOrganizationSeeder` used to grant membership from a non-empty legacy identifier (`tkid`,
`rebelforum`, …) without checking the club's permission flag (`p501`, `pRebel`, …), and
`assignUnit()` granted squad membership from `squad` without checking `p501`. The importer now
checks both.

- **Membership:** only (trooper, club) pairs with `pX = 0` whose `tt_trooper_organizations` row is
  already `retired`/`reserve` are corrected: the row is soft-deleted and the assignment's
  `is_member` cleared (soft-deleted too if it has no moderator/notify purpose). Same for false
  squad assignments. Pairs still `active` may be real later joins — they are left alone and
  reported in `Fix408AmbiguousMemberships`, with credited shifts split into TT1.0 (decided by the
  legacy signup regardless) and TT2.0 (riding on this membership).
- **Credit:** TT2.0 signups crediting a corrected false club lose that club; if nothing else is
  left they are re-resolved by the rules above, or cleared and reported
  (`Fix408OutstandingCredit`). TT1.0 signups are left to Fix407/Fix409.
- **Achievements:** club-scoped achievements for a corrected false club are hard-deleted (the
  unique index on `(trooper_id, type, organization_coalesce_id)` includes soft-deleted rows, so a
  soft delete would block a future legitimate milestone).

### Fix409 — final consistency check

Checks every `ATTENDED` row with credit and removes credit the shift could never have earned — it
never adds any:

- TT1.0 signup: any club the legacy signup didn't record.
- TT2.0 signup: any club joined after the shift, or provably left before it.

If nothing survives, the row is re-resolved by the rules above, or cleared and reported. Credit
with no membership evidence either way is kept and reported. Rows credited only through
`organization_id` are reported, not changed. Finally every club-scoped troop-count achievement is
checked against the trooper's remaining credited shifts and hard-deleted if it no longer meets its
threshold.

### Running them

Every fix runs in one transaction, writes a row only when its value changes, and is safe to re-run
— a second pass reports zero changes (outstanding rows are re-reported each run). All four need
the legacy `troopers`, `events`, `event_sign_up` and `costumes` tables; without them, every row is
treated as a TT2.0 signup and a warning is printed.

Recommended order on production:

1. Back up the database and confirm the legacy tables are present.
2. `Fix408` — correct false memberships first, so later inference can't use them.
3. `Fix406` — restore missing credit.
4. `Fix407` — make TT1.0 signups match their legacy signup.
5. `Fix409` — final impossible-credit sweep and achievement cleanup.
6. `php artisan tracker:calculate-trooper-achievements --without-notifications` — Fix408 and
   Fix409 hard-delete club milestones the corrected credit no longer supports; the recalculation
   recreates any still earned with `notification_sent_at` empty, so without the flag the daily
   roundup would re-announce milestones troopers already had. (It also means milestones newly
   earned through restored credit go out silently.)
7. Re-run 408 → 406 → 407 → 409 and confirm every changed count is 0, then review the emails.

Fix409 counts a trooper's credited shifts exactly as the recalculation does (`costume_organization_ids`,
falling back to `organization_id`), so it never removes a milestone the recalculation would
immediately recreate.

Running them in numeric order converges to the same result; 408 first just avoids writing credit
that is immediately removed again.

---

## Adding a New Fix

1. Create `database/seeders/Issues/Fix<issue-number>.php` with namespace `Database\Seeders\Issues`.
2. Extend `Illuminate\Database\Seeder` and implement `run(): void`.
3. Wrap destructive changes in `DB::transaction(...)`.
4. Use `$this->command?->info(...)` to report results.
5. Add an entry to this document.
