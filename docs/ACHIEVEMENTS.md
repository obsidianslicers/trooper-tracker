# Achievements

TroopTracker has two recognition systems:

| System | Purpose | Storage | How records are created |
|---|---|---|---|
| Achievements | Automatic milestones and metrics based on trooper activity | `tt_trooper_achievements` | Scheduled recalculation command |
| Awards | Manual organization-defined recognition | `tt_awards`, `tt_award_troopers` | Admin award tools |

This document covers automatic achievements.

---

## Scheduled calculation

Achievements are recalculated by:

```bash
php artisan tracker:calculate-trooper-achievements
```

The scheduler runs this daily at 2:00 AM in `routes/console.php`.

The command dispatches `RecalculateTrooperRankCommand`, handled by:

```text
app/Features/Troopers/Commands/RecalculateTrooperRankCommandHandler.php
```

The handler processes troopers in chunks and writes rows to `tt_trooper_achievements`.

---

## Achievement types

Achievement definitions live in:

```text
app/Enums/AchievementType.php
```

The enum controls:

- Stored `type` value
- Whether the value is numeric or boolean via `valueType()`
- Icon via `toIcon()`
- Title via `toTitle()`
- Description via `toDescription()`
- Whether it is a public milestone via `isMilestone()`

### Metrics

Metrics are updated every calculation run with `updateOrCreate`.

| Type | Meaning |
|---|---|
| `TROOPER_RANK` | Rank position among all troopers by attended shift count |
| `TROOPER_SHIFTS` | Total attended shifts |
| `VOLUNTEER_HOURS` | Total effective charity hours from attended closed shifts |
| `DIRECT_FUNDS` | Total direct funds from attended closed shifts |
| `INDIRECT_FUNDS` | Total indirect funds from attended closed shifts |
| `DONATION_MONTHS` | Distinct donation months from XenForo/local donation data |
| `TOTAL_DONATED` | Total donated from XenForo/local donation data |

Metrics are global only.

### Milestones

Milestones are created once with `firstOrCreate`, preserving the original `achievement_date`.

Troop-count milestones:

```text
FIRST_TROOP
TROOPED_10
TROOPED_25
TROOPED_50
TROOPED_75
TROOPED_100
TROOPED_150
TROOPED_200
TROOPED_250
TROOPED_300
TROOPED_400
TROOPED_500
TROOPED_501
```

Donation milestones:

```text
SUPPORTER_12_MONTHS
SUPPORTER_24_MONTHS
SUPPORTER_36_MONTHS
SUPPORTER_60_MONTHS
DONATED_100
DONATED_250
DONATED_500
DONATED_1000
```

---

## Global vs. club-scoped achievements

`tt_trooper_achievements.organization_id` determines the scope:

| `organization_id` | Meaning |
|---|---|
| `null` | Global achievement |
| organization ID | Club-scoped achievement for that top-level club |

Global troop milestones count all attended shifts.

Club-scoped troop milestones mirror the same troop-count thresholds, but count only shifts credited to that top-level club.

### Why `organization_scope_id` exists

The table also has a generated `organization_scope_id` column:

```text
COALESCE(organization_id, 0)
```

This exists only to enforce uniqueness.

MySQL allows multiple `NULL` values inside a unique index, so a unique index on `(trooper_id, type, organization_id)` would not reliably prevent duplicate global achievements. The generated scope column turns global achievements into scope `0`, allowing this unique constraint:

```text
(trooper_id, type, organization_scope_id)
```

Application code should use `organization_id`; `organization_scope_id` is a database helper.

---

## Club troop credit rules

Club milestone counts are calculated from attended `EventTrooper` records.

For each attended shift:

1. If `costume_organization_ids` is non-empty, those organizations receive credit.
2. Otherwise, `event_troopers.organization_id` receives credit.
3. Credited organizations roll up to their top-level club using `tt_organizations.node_path`.
4. A shift can count only once per top-level club, even if multiple credited org IDs belong to the same club.

This matches the service-record troop credit behavior.

---

## Notifications

When a milestone row is newly created, `RecalculateTrooperRankCommandHandler` dispatches:

```text
App\Jobs\SendTrooperMilestoneNotificationsJob
```

That job notifies administrators and in-scope moderators who have opted into `trooper_milestones` notifications for one of the trooper's member organizations.

Club-scoped milestones use the same notification path as global milestones. Notification bodies and milestone emails use `TrooperAchievement::display_description`, so scoped milestones include club context, for example:

```text
501st Legion: 10 Troops - Frontier Service Ribbon
```

---

## Display

Recent milestone activity is loaded by:

```text
app/Features/Troopers/Queries/GetTrooperAchievementsQueryHandler.php
```

Trooper service-record summaries are loaded by:

```text
app/Features/Troopers/Queries/GetTrooperServiceRecordQueryHandler.php
```

Service-record milestones are grouped in this order:

1. Global troop-count milestones
2. Club troop-count milestones, grouped by club name
3. Donation milestones
4. Other milestone types

`TrooperAchievement::display_description` adds club context when `organization_id` is set.

---

## Adding a new achievement

1. Add a case to `AchievementType`.
2. Update `valueType()` if the achievement is numeric.
3. Add icon/title/description handling in `toIcon()`, `toTitle()`, and `toDescription()`.
4. Add the case to `isMilestone()` if it should appear in milestone feeds and trigger milestone notifications.
5. Add calculation logic in `RecalculateTrooperRankCommandHandler`.
6. Add display ordering/grouping in `TrooperAchievement` if needed.
7. Add tests for calculation, storage, display, and notification behavior.

For another troop-count threshold, add it to the threshold map in `RecalculateTrooperRankCommandHandler`. The same threshold map is used for both global and club-scoped troop milestones.

For a non-troop club achievement, decide whether it should be global-only or organization-scoped before adding storage/calculation behavior.

---

## Common checks

Run the recalculation manually:

```bash
php artisan tracker:calculate-trooper-achievements
```

Run focused tests:

```bash
php artisan test \
  tests/Feature/Models/TrooperAchievementTest.php \
  tests/Feature/Features/Troopers/Commands/RecalculateTrooperRankCommandHandlerTest.php \
  tests/Feature/Features/Troopers/Queries/GetTrooperAchievementsQueryHandlerTest.php \
  tests/Feature/Features/Troopers/Queries/GetTrooperServiceRecordQueryHandlerTest.php \
  tests/Feature/Jobs/SendTrooperMilestoneNotificationsJobTest.php \
  tests/Feature/Mail/Admin/Troopers/TrooperMilestoneMailTest.php
```
