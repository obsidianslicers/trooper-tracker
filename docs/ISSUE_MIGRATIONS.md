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
unconditionally cleared both `costume_organization_ids` and `organization_id` on a row whenever
org-selection data wasn't submitted for it — silently destroying any existing troop credit,
including the `organization_id` legacy fallback. `EventTrooper` only audits the `status` column,
so the original values cannot be recovered from an audit trail. A residual gap left `organization_id`
being nulled unconditionally on every save even after #262 landed; that live bug is fixed alongside
this seeder.

**What it does:** Scans `EventTrooper` records with `status = attended`, `organization_id IS NULL`,
and `costume_organization_ids` null or empty — i.e. rows with no credit source at all. For each,
re-derives credit from current costume approvals / membership via
`EventTrooper::getEligibleCreditOrganizations()` (the same resolver the self-service attendance
flow uses):

- **One eligible top-level club** — unambiguous; populate `costume_organization_ids` with that
  club's eligible org IDs.
- **More than one eligible top-level club** — ambiguous (the self-service flow would have asked
  the trooper to choose); rather than guess, all eligible clubs are credited and counted
  separately (`resolved_multi_club`) so they can be audited afterward.
- **No eligible club** — cannot determine, skipped and requires manual review.

Outputs counts for scanned/resolved (single + multi club)/skipped records. If any records were
skipped, queues a `Fix406OutstandingCredit` email (`app/Mail/Fix406OutstandingCredit.php`) to
every administrator trooper, listing each skipped record's trooper, event, costume, and
`EventTrooper` ID so they can be reviewed manually. No email is sent if nothing was skipped.

**When to run:** Once, against any environment running before the `organization_id`-nulling fix
in `UpdateTroopersSubmitController` (the forward fix ships alongside this seeder). Run on
production before reloading affected service record pages.

**Bug fix (discovered while investigating widespread missing credit on the Missing Credits
page):** the `costume_organization_ids is null OR costume_organization_ids = '[]'` check used a
plain `orWhere('[]')`. Comparing a MySQL `JSON` column to a string via a bound parameter never
does JSON-aware equality — it's only JSON-aware when the string is a literal written directly in
the SQL text — so this condition never actually matched the `'[]'` case, only true SQL `NULL`.
Since every current write path stores an empty *array* (`'[]'`) rather than `NULL` when there's no
credit, `Fix406` had — until this was fixed — only ever been able to resolve a small fraction of
its intended target (confirmed: 18 true-`NULL` rows vs. 2,476 `'[]'` rows in one affected
database). The condition now uses `orWhereJsonLength(..., 0)`, which is JSON-aware and matches
both cases. Re-run `Fix406` after upgrading to pick up any backlog it previously missed.

---

## Fix407

**Issue:** After fixing `Fix406`'s JSON-comparison bug (see above), a small residue of records
still have no credit source and no live-eligible organization — mostly troopers with no current
active club assignment at all (retired, command staff, N/A membership). `Fix406`'s live resolver
(current costume approvals / membership) has no signal to work with for these.

**What it does:** Only processes records `Fix406` already can't resolve
(`getEligibleCreditParentOrganizations()` empty) — it does not duplicate `Fix406`'s single/multi-club
resolution, so **run `Fix406` first**. For each such record, looks up the trooper's original
signup for that exact shift in the legacy (pre-2.0) `event_sign_up`/`costumes` tables — matched via
`event_sign_up.troopid = event_trooper.event_shift_id` and `event_sign_up.trooperid =
event_trooper.trooper_id`, both ids preserved 1:1 from the old tracker. The legacy `costumes.club`
tag is still present even for costumes the 2.0 import deliberately excluded from migration
(`N/A`, `Handler`, `Command Staff`). If that legacy club maps to a current organization (via the
same club map `TrooperCostumeSeeder` uses), credit is backfilled from it.

Requires the legacy `event_sign_up`/`costumes` tables to still be present (skips gracefully,
matching the `Fix246` pattern, if they're not — safe on fresh installs). A legacy club of `4`
("Other") or one with no equivalent current organization is treated the same as no legacy record:
skipped and included in a `Fix407OutstandingCredit` email to every administrator
(`app/Mail/Fix407OutstandingCredit.php`), listing each skipped record's trooper, event, costume,
and a note on why the legacy lookup couldn't help either.

**When to run:** Once, immediately after `Fix406`, on any environment that was imported from the
legacy (pre-2.0) tracker and still has leftover missing-credit records after `Fix406` runs.

---

## Fix408

**Issue:** `TrooperOrganizationSeeder` (the one-time Florida Garrison import) determined club
membership purely from whether a legacy identity field (`tkid`, `rebelforum`, ...) was non-empty,
never checking the club's own permission flag (`p501`, `pRebel`, ...). The old tracker required
every trooper to fill in a TKID-style field in its unified signup form regardless of their actual
club, so troopers ended up with an active membership — and, once `Fix242`'s orphaned-membership
repair ran, an active `TrooperAssignment` — for a club they never belonged to (e.g. a Rebel Legion
trooper credited for 501st Legion). That false membership fed troop credit and club-scoped
achievement milestones for years, independent of `Fix406`/`Fix407`. The importer itself is fixed
alongside this seeder: it now checks the permission flag for every club, generalizing a check that
previously only existed for Droid Builders.

**What it does:** Scans every legacy trooper against every club for the mismatch (permission flag
says not-a-member, but a currently-active `TrooperAssignment` exists anyway). Only pairs where the
current `tt_trooper_organizations` row for that club is already `retired`/`reserve` are corrected
automatically — someone already flagged the membership as wrong, it just never propagated to the
`TrooperAssignment` row. For those:

- Soft-deletes the false `tt_trooper_organizations` row itself — it carries the fabricated
  identifier (the stray legacy "TKID") and a status that implies a real membership that never
  happened, so it's removed rather than left around with the identifier still visible.
- Clears `is_member` on the false `TrooperAssignment` (soft-deletes it too if it carries no
  moderator/notify purpose).
- Strips the false club's org id out of every affected `EventTrooper.costume_organization_ids`,
  all-time (not just rows a prior backfill touched). If credit remains after stripping, that's the
  fix. If nothing remains, re-tries live eligibility, then the legacy `event_sign_up`/`costumes`
  fallback — **excluding the false club from that fallback's result too**, since the legacy signup
  data can carry the exact same false attribution the membership did (same old tracker, same root
  cause). If still nothing, clears the row to empty credit and reports it
  (`Fix408OutstandingCredit`, same pattern as `Fix406`) rather than leaving the false value in
  place just because nothing better was found.
- **Hard-deletes** (not soft-deletes) any club-scoped `tt_trooper_achievements` row tied to the
  false club. Hard-delete is required here: the table's unique index
  (`trooper_id, type, organization_coalesce_id`) doesn't exclude soft-deleted rows, and the
  recalculation command's existence check doesn't use `withTrashed()` — a soft-deleted row would
  permanently block any future legitimate milestone for that exact trooper/type/club combination.

The same bug exists one level down: `TrooperOrganizationSeeder::assignUnit()` grants `is_member` on
a Florida Garrison squad purely from the legacy `squad` field matching, also never checking `p501`.
`Fix408` corrects this the same way (clearing the false squad `TrooperAssignment`, crediting the
correction toward the 501st root for credit/achievement cleanup) and the importer fix covers both
`assignOrganizationAndRegion()` and `assignUnit()`.

Pairs where the `tt_trooper_organizations` row is still `active` are **not** touched — the trooper
could have legitimately joined later — and are instead emailed to administrators
(`Fix408AmbiguousMemberships`) with the club, status, and how many credited shifts/achievements
currently ride on that membership, for manual confirmation.

**When to run:** Once, after `Fix406`/`Fix407`, on any environment imported from the Florida
Garrison legacy tracker. Afterward, run `php artisan tracker:calculate-trooper-achievements` so any
legitimately-earned club milestone (e.g. for the trooper's real club) is created fresh.

---

## Adding a New Fix

1. Create `database/seeders/Issues/Fix<issue-number>.php` with namespace `Database\Seeders\Issues`.
2. Extend `Illuminate\Database\Seeder` and implement `run(): void`.
3. Wrap destructive changes in `DB::transaction(...)`.
4. Use `$this->command?->info(...)` to report results.
5. Add an entry to this document.
