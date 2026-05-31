# Troop Credit

This document explains how troop credit is attributed to organizations when a trooper attends an event shift — from how credit data is stored on the `EventTrooper` record, through attendance confirmation, to how it's surfaced on service records and reports.

---

## Overview

When a trooper attends a shift, their attendance can credit one or more organizations. Two fields on `tt_event_troopers` serve distinct purposes:

| Field | Purpose |
|---|---|
| `organization_id` | Capacity tracking only — which per-org slot was occupied at signup |
| `costume_organization_ids` | Credit attribution — JSON array of org IDs the trooper selected or that were auto-resolved from costume approvals |

**Credit always flows through `costume_organization_ids`** for records created after the self-service flow was updated. For older records that have `organization_id` set but `costume_organization_ids` empty, the service record query falls back to `organization_id` for backward compatibility.

- **Current path** — credit goes to orgs in `costume_organization_ids`
- **Legacy fallback** — if `costume_organization_ids` is empty and `organization_id` is set, credit goes to that org
- **No match** — both fields are empty/null: no org receives credit

---

## Organization Hierarchy

Organizations are hierarchical — a club contains regions, regions contain units. The `node_path` column encodes this as a colon-delimited chain of IDs:

```
501           ← top-level club (garrison)
501:42        ← region inside that club
501:42:7      ← unit inside that region
```

Credit attributed to a child org rolls up to its parent. When the service record queries whether a shift credits org `501`, it checks whether the credited org's `node_path` starts with `"501"` — so a shift credited to region `501:42` also counts for the club `501`.

---

## How `costume_organization_ids` Gets Populated

The `EventTrooperObserver` computes `costume_organization_ids` automatically whenever `costume_id` or `backup_costume_id` changes on an `EventTrooper` record.

```mermaid
flowchart TD
    A[EventTrooper saved] --> B{costume_id or backup_costume_id dirty?}
    B -->|No| Z[No change to org IDs]
    B -->|Yes| C[Load event_organizations where can_attend = true for this shift]
    C --> D[Query OrganizationCostume: costume matches + org in can_attend list]
    D --> E[Filter: trooper has a TrooperCostume approval for that org_costume]
    E --> F[Store matching org IDs → costume_organization_ids]
    F --> G{organization_id is null AND exactly one org AND that org has per-org limits?}
    G -->|Yes| H[Auto-set organization_id to that org]
    G -->|No| Z
```

**Result**: `costume_organization_ids` always reflects which orgs the trooper is approved to represent with their chosen costume, within the scope of orgs allowed at this specific event.

The auto-set of `organization_id` (last branch) is a convenience for capacity tracking: if the costume unambiguously resolves to one per-org-limited org, that org is stamped into `organization_id` so slot counts stay accurate.

---

## Attendance Credit Assignment

Credit is finalized when a trooper's status is set to `ATTENDED`. This can happen via the trooper's self-service link, admin roster update, or shift simulation.

### Trooper self-service flow

```mermaid
flowchart TD
    A[Trooper clicks attended link in email] --> B{Event still allows status updates?}
    B -->|No| X[Flash error — no change]
    B -->|Yes| E[Call getEligibleCreditParentOrganizations]
    E --> F{How many top-level clubs eligible?}
    F -->|More than one| G[Redirect to club selection page]
    F -->|Zero or one| H{costume_organization_ids empty?}
    H -->|No — credit already resolved| D[Set status = ATTENDED, save]
    H -->|Yes| I[Load getEligibleCreditOrganizations]
    I --> J{Any eligible orgs?}
    J -->|Yes| K[Populate costume_organization_ids from eligible orgs]
    K --> D
    J -->|No| D
```

### Multi-club selection (when trooper must choose)

Shown when `getEligibleCreditParentOrganizations` returns more than one top-level club — the trooper selects which club(s) should receive credit.

```mermaid
flowchart TD
    A[Trooper submits club selection form] --> B{Submitted org IDs valid?}
    B -->|No| X[422 Unprocessable]
    B -->|Yes| C[Map selected parent org IDs → child org IDs via childOrgIdsForSelectedParents]
    C --> D[Set costume_organization_ids = child_org_ids]
    D --> E[Set status = ATTENDED]
    E --> F[Save — organization_id intentionally left null]
```

`organization_id` is intentionally **not** set here — multi-club credit flows via `costume_organization_ids` so more than one club can be credited.

### Admin roster update flow

```mermaid
flowchart TD
    A[Admin submits trooper roster update] --> B[For each event_trooper in submission]
    B --> C[Set new status]
    C --> D[Collect submitted organization_ids from form]
    D --> E[Filter: org must be one the trooper belongs to AND within admin's access scope]
    E --> F{Any valid org IDs?}
    F -->|Yes| G[Set costume_organization_ids = valid org IDs, organization_id = null]
    F -->|No| H[Set costume_organization_ids = null, organization_id = null]
    G --> I[Save]
    H --> I
```

The admin path always clears `organization_id` and sets `costume_organization_ids`, routing credit through Rule 2 exclusively.

---

## Service Record: Per-Org Troop Count

The service record page shows each organization the trooper belongs to, with a `troop_count` of how many closed shifts credited that org. This is computed in PHP using the `HasOrgCreditAnnotation` trait.

```mermaid
flowchart TD
    A[Load trooper's organizations from TrooperOrganization] --> B[Load recent closed event shifts for trooper]
    B --> C[Set shift.event_trooper = event_troopers.first on each shift]
    C --> D[Collect all candidate org IDs from shifts\norganization_id + costume_organization_ids]
    D --> E[Query Organization table for candidate orgs → keyed by ID with node_path]
    E --> F[For each ATTENDED shift]
    F --> G{organization_id set on event_trooper?}
    G -->|Yes — Rule 1| H[Look up candidate org's node_path\nFind trooper org where node_path is a prefix of it]
    G -->|No — Rule 2| I[Map each costume_org_id → node_path via candidate_orgs\nFind trooper orgs where any costume node_path starts with trooper org's node_path]
    H --> J[Increment troop_count for matched trooper org]
    I --> J
    J --> F
    F -->|All shifts processed| K[Attach troop_count to each organization]
    K --> L[Annotate each shift's event_trooper with credited_org_names for display]
```

**Key invariant**: `str_starts_with(credited_org.node_path, trooper_org.node_path)` — a shift credited to any child of the trooper's org still increments that org's count. A shift credited to an unrelated org does not.

---

## Reports: SQL Attribution

The `HasOrgAttributionQuery` trait applies org filtering at the SQL level for reports like the Trooper Event Summary. It accepts either a single `Organization` model or a list of `accessible_org_ids` (root-level IDs that a moderator can see).

```mermaid
flowchart TD
    A[Report query with org filter] --> B{Single org specified?}
    B -->|Yes| C[WHERE organization_id = org.id\nOR organization_id IS NULL\n   AND JSON_CONTAINS costume_organization_ids, org.id]
    B -->|No| D{accessible_org_ids provided?}
    D -->|Yes| E[WHERE EXISTS org with matching node_path root\n   OR JSON_OVERLAPS costume_organization_ids, accessible_org_ids]
    D -->|No — no filter| F[No attribution WHERE clause added]
    C --> G[Scoped result set]
    E --> G
    F --> G
```

The multi-org path uses `SUBSTRING_INDEX(node_path, ':', 1)` to extract the root club ID from a candidate org's path, then checks it against the `accessible_org_ids` list. This means a shift credited to region `501:42` is visible to a moderator whose access covers root org `501`.

---

## Where Credit Data Lives

| Table | Column | Role |
|---|---|---|
| `tt_event_troopers` | `organization_id` | Rule 1 — explicit org chosen at signup or by observer auto-set |
| `tt_event_troopers` | `costume_organization_ids` | Rule 2 — orgs derived from costume approvals or admin/trooper selection |
| `tt_organization_costumes` | `organization_id`, `costume_id` | Links costumes to orgs |
| `tt_trooper_costumes` | `trooper_id`, `organization_costume_id` | Trooper's approval to wear a costume for a given org |
| `tt_event_organizations` | `can_attend`, `organization_id` | Which orgs may attend an event/shift |
| `tt_organizations` | `node_path` | Hierarchy used for prefix-based ancestor matching |

## Key Code Locations

| Concern | File |
|---|---|
| Compute `costume_organization_ids` on save | `app/Models/Observers/EventTrooperObserver.php` |
| Attendance credit assignment (self-service) | `app/Http/Controllers/Events/ShiftCompleteController.php` |
| Multi-club selection | `app/Http/Controllers/Events/ShiftCompleteClubController.php` |
| Admin roster credit override | `app/Http/Controllers/Admin/Events/UpdateTroopersSubmitController.php` |
| PHP troop count + org annotation | `app/Features/Troopers/Queries/HasOrgCreditAnnotation.php` |
| SQL attribution WHERE clause | `app/Features/Events/Queries/HasOrgAttributionQuery.php` |
| Service record handler | `app/Features/Troopers/Queries/GetTrooperServiceRecordQueryHandler.php` |
| Event summary report handler | `app/Features/Reports/Queries/GetTrooperEventSummaryQueryHandler.php` |

## Important Behaviors

**No join-date gate.** Credit is not conditioned on when a trooper joined an org. A shift that credits an org counts toward that org's `troop_count` regardless of the trooper's join date. `costume_organization_ids` must be non-empty — or the legacy `organization_id` fallback must match — for any org to receive credit.

**`organization_id` is capacity-only going forward.** The self-service flow now always offers club selection and always writes credit into `costume_organization_ids`, regardless of whether `organization_id` is set. `organization_id` and `costume_organization_ids` can coexist on the same record: one tracks which capacity slot was used, the other tracks which clubs receive credit. The admin path always clears `organization_id` when setting `costume_organization_ids`.

**Multi-club credit always flows through `costume_organization_ids`.** When a trooper's costume spans multiple clubs and they confirm attendance for multiple clubs, all club IDs live in `costume_organization_ids`. This allows one shift to credit more than one `troop_count` entry.
