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

## Fix406

**Issue:** Before the admin roster-update controller was fixed (#262), every admin roster save
cleared both `costume_organization_ids` and `organization_id` whenever org-selection data wasn't
submitted for a row, destroying any existing troop credit with no way to recover it from an audit
trail (`EventTrooper` only audits `status`). A residual gap kept nulling `organization_id` on
every save even after #262; that's fixed alongside this seeder.

**What it does:** Scans `EventTrooper` records with `status = attended`, `organization_id IS
NULL`, and `costume_organization_ids` null or empty, then re-derives credit via
`EventTrooper::getEligibleCreditOrganizations()` — the same resolver the self-service attendance
flow uses:

- One eligible top-level club: populate `costume_organization_ids` with it.
- More than one eligible club: ambiguous, credit all of them and count separately
  (`resolved_multi_club`) for later audit.
- No eligible club: skipped, needs manual review.

Prints scanned/resolved/skipped counts. If anything was skipped, queues a `Fix406OutstandingCredit`
email to every administrator listing the trooper, event, costume, and `EventTrooper` ID for each.

**When to run:** Once, on any environment running before the `organization_id`-nulling fix in
`UpdateTroopersSubmitController` (the forward fix ships with this seeder). Run before reloading
affected service record pages.

**Bug found while investigating widespread missing credit:** the empty-credit check used
`orWhere('costume_organization_ids', '[]')`. MySQL only does JSON-aware equality on a literal
written into the SQL text, not on a bound parameter, so this never actually matched the `'[]'`
case — only true `NULL`. Every current write path stores `'[]'`, not `NULL`, for no-credit rows,
so `Fix406` had only ever resolved a sliver of what it was supposed to (18 `NULL` rows vs. 2,476
`'[]'` rows in one affected database). Changed to `orWhereJsonLength(..., 0)`. Re-run `Fix406` to
pick up the backlog it missed before this fix.

---

## Fix407

**Issue:** Even after `Fix406`'s JSON-comparison fix, a handful of records still have no credit
source and no live-eligible organization — mostly troopers with no current active club assignment
at all (retired, command staff, N/A membership). `Fix406`'s resolver has nothing to work with for
these.

**What it does:** Only processes records `Fix406` can't resolve
(`getEligibleCreditParentOrganizations()` empty) — doesn't duplicate its single/multi-club logic,
so run `Fix406` first. For each, looks up the trooper's original signup for that shift in the
legacy (pre-2.0) `event_sign_up`/`costumes` tables, joined via `event_sign_up.troopid =
event_shift_id` and `event_sign_up.trooperid = trooper_id` (both ids carried over 1:1 from the old
tracker). Those tables still tag a costume's club even where the 2.0 import skipped migrating it
(`N/A`, `Handler`, `Command Staff`). If that legacy club maps to a current organization, credit
backfills from it.

Needs the legacy `event_sign_up`/`costumes` tables to exist — skips cleanly if not, same as
`Fix246`. A legacy club of `4` ("Other") or one with no current equivalent is treated as no
record: skipped and added to a `Fix407OutstandingCredit` email to every administrator, with a note
on why the legacy lookup didn't help either.

**When to run:** Once, right after `Fix406`, on any environment imported from the legacy tracker
that still has missing-credit records afterward.

---

## Fix408

**Issue:** `TrooperOrganizationSeeder` (the Florida Garrison import) granted club membership from
a non-empty legacy identity field (`tkid`, `rebelforum`, ...) alone, never checking the club's own
permission flag (`p501`, `pRebel`, ...). The old signup form required an identifier from everyone
regardless of club, so troopers ended up with an active membership for a club they were never in
— and once `Fix242`'s orphaned-membership repair ran, a real `TrooperAssignment` too (e.g. a Rebel
Legion trooper credited for 501st Legion). That fed troop credit and club-scoped achievements for
years, independent of `Fix406`/`Fix407`. The importer is fixed alongside this seeder — it now
checks the permission flag for every club, generalizing a check that previously only covered Droid
Builders.

**What it does:** Checks every legacy trooper against every club for the mismatch. Only corrects a
pair automatically when the current `tt_trooper_organizations` row for that club is already
`retired`/`reserve` — someone already flagged it wrong, it just never reached the
`TrooperAssignment`. For those:

- Soft-deletes the false `tt_trooper_organizations` row, including its fabricated identifier.
- Clears `is_member` on the false `TrooperAssignment` (soft-deletes it too if it carries no
  moderator/notify purpose).
- Strips the false club out of every affected `EventTrooper.costume_organization_ids`, all-time,
  not just rows a prior backfill touched. Falls back to live eligibility, then the legacy
  `event_sign_up`/`costumes` lookup (also filtered against the false club, since the old tracker's
  signup data can carry the same wrong attribution), then clears the row to empty credit and
  reports it (`Fix408OutstandingCredit`) if nothing resolves it.
- Hard-deletes (not soft-deletes) any club-scoped `tt_trooper_achievements` row tied to the false
  club. Has to be a hard delete: the table's unique index on
  `(trooper_id, type, organization_coalesce_id)` doesn't exclude soft-deleted rows, and the
  recalculation command doesn't query `withTrashed()`, so a soft-deleted row would permanently
  block any future legitimate milestone for that trooper/type/club.

Same bug one level down: `TrooperOrganizationSeeder::assignUnit()` grants a Florida Garrison squad
membership from the legacy `squad` field alone, also without checking `p501`. `Fix408` corrects
this the same way, crediting it toward the 501st root for the credit/achievement passes. The
importer fix covers both `assignOrganizationAndRegion()` and `assignUnit()`.

Pairs still marked `active` aren't touched — the trooper may have joined for real since the import
— and go to administrators instead, via `Fix408AmbiguousMemberships`, with the club, status, and
how many credited shifts/achievements ride on that membership.

**When to run:** Once, after `Fix406`/`Fix407`, on any environment imported from the Florida
Garrison legacy tracker. Follow with `php artisan tracker:calculate-trooper-achievements` so any
club milestone the trooper actually earned gets created.

---

## Adding a New Fix

1. Create `database/seeders/Issues/Fix<issue-number>.php` with namespace `Database\Seeders\Issues`.
2. Extend `Illuminate\Database\Seeder` and implement `run(): void`.
3. Wrap destructive changes in `DB::transaction(...)`.
4. Use `$this->command?->info(...)` to report results.
5. Add an entry to this document.
