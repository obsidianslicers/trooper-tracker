# Charity Tracking

TroopTracker tracks charitable impact data per event shift: the organization served, volunteer
hours contributed, and funds raised. All five fields live on `tt_event_shifts`.

---

## Fields

| Column | Type | Default | Description |
|---|---|---|---|
| `charity_name` | varchar(512) | null | Name of the charitable organization |
| `charity_hours` | integer | null | Override for volunteer hours (see below) |
| `charity_direct_funds` | integer | 0 | Direct funds raised (dollars) |
| `charity_indirect_funds` | integer | 0 | Indirect funds raised (dollars) |
| `charity_notes` | text | null | Free-form notes about the charity appearance |

---

## Volunteer Hours: null vs. override

`charity_hours` has two distinct states:

| Value | Meaning |
|---|---|
| `null` | Auto-calculate from shift duration (`shift_ends_at - shift_starts_at`) |
| integer | Absolute override — use this value instead of the shift duration |

This mirrors TT 1.0 behaviour where hours were auto-filled from the shift window and admins
could adjust the final credit up or down. The admin UI shows the calculated shift duration as
a placeholder so the field appears pre-filled; leaving it blank keeps `charity_hours = null`
and auto-calculation applies everywhere.

### `effective_charity_hours` accessor

`App\Models\EventShift` exposes a computed accessor that resolves the value in one place:

```php
public function getEffectiveCharityHoursAttribute(): int
{
    return $this->charity_hours ?? (int) $this->shift_starts_at->diffInHours($this->shift_ends_at);
}
```

All business logic reads `$shift->effective_charity_hours` — never `charity_hours` directly.

---

## Where it's used

### Rank calculation

`app/Features/Troopers/Commands/RecalculateTrooperRankCommandHandler.php`

Sums `effective_charity_hours` across all of a trooper's attended shifts to compute total
volunteer hours for rank thresholds.

### Achievement seeder

`database/seeders/FloridaGarrison/TrooperAchievementSeeder.php`

Same sum, used when backfilling achievement records from legacy data.

### Dashboard metrics

`app/Features/Reports/Queries/GetDashboardMetricsQueryHandler.php`

Loads `shift_starts_at`, `shift_ends_at`, and `charity_hours` then calls
`$shifts->sum('effective_charity_hours')` to report total volunteer hours across all closed
events within a lookback window.

### Donation event summary report

`app/Features/Reports/Queries/GetDonationEventSummaryQueryHandler.php`

Raw SQL subquery that uses `COALESCE` to fall back to shift duration when no override is set:

```sql
SUM(COALESCE(charity_hours, TIMESTAMPDIFF(HOUR, shift_starts_at, shift_ends_at)))
```

---

## Admin UI

The Charity tab at `/admin/events/{event}/charity` shows a card per shift. Hours field
renders as a plain `<input>` with the auto-calculated duration as a placeholder. Submitting
blank saves `null`; submitting a number saves the absolute override. The tab is always
editable regardless of event status.

---

## Legacy import

The legacy TT 1.0 database stored `charityAddHours` as a signed offset (e.g. `-1`, `0`, `+2`).
`database/seeders/Issues/Fix246.php` converts these on import:

```
offset == 0  →  null          (use auto-calculate)
offset != 0  →  duration + offset   (absolute override preserving the adjustment)
```

---

## Historical note

Prior to migration `2026_06_11`, charity data lived on `tt_events` (one record per event).
The migration moved all five fields to `tt_event_shifts` (one record per shift) and copied
the existing event-level value to the first shift of each event. The `tt_events` charity
columns were then dropped.
