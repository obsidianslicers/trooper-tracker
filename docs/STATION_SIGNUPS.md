# Station Signups

Station signups let command staff split a single event shift into named capacity buckets. This is useful when a shift needs troopers assigned to specific locations or duties, such as an information booth, photo area, stage door, or parking lot.

Stations are optional and are configured per shift. An event can have some shifts with stations and other shifts without stations.

---

## Data Model

Station definitions are stored in `tt_event_shift_stations`.

Each station belongs to one `tt_event_shifts` row and has:

| Column | Meaning |
|---|---|
| `event_shift_id` | The shift this station belongs to |
| `name` | Display name shown to command staff and troopers |
| `troopers_allowed` | Requested number of `GOING` troopers for the station |
| `sequence` | Sort order used by the admin drag-and-drop UI |

Trooper signups store their station in `tt_event_troopers.event_shift_station_id`.

This column is nullable. If it is `null`, the signup is not assigned to a station. Guest signups do not use stations.

---

## Admin Setup

Command staff manage stations from the admin event Shifts tab.

For each saved shift:

- If the shift has no stations, the UI shows a subtle `Stations` action.
- Clicking `Stations` expands the station editor and adds the first row.
- If the shift already has stations, the station editor is shown under that shift.
- Command staff can add station rows, set the station name, set the requested count, drag stations to reorder them, and remove stations that do not have assigned signups.

Station changes are saved with the shift form.

Removing a station is blocked if any `tt_event_troopers` rows are assigned to it. This preserves roster history and avoids leaving existing signups pointed at a deleted assignment bucket.

---

## Signup Behavior

If a shift has at least one station, trooper signups for that shift must choose a station.

This applies to:

- Self signups
- Friend signups
- Handler signups
- Moderator-added troopers

Guests stay outside station capacity in the current implementation.

When a station is selected:

1. The app counts current `GOING` `tt_event_troopers` for that station.
2. If the count is below `troopers_allowed`, the signup becomes `GOING`.
3. If the station is full, the signup becomes `STAND_BY`.

While a shift is open, signup owners can switch their own station from the event page. Admins can also edit station assignments from the admin roster backend.

Switching into a full station moves or keeps the signup on `STAND_BY`.

---

## Capacity Rules

Stations are the capacity source for shifts that have stations.

For a stationed shift:

- Station capacity decides whether a trooper is `GOING` or `STAND_BY`.
- Global shift trooper limits do not block stationed signups.
- Organization trooper and handler limits do not block stationed signups.
- Handler and trooper station capacity is shared; stations count `EventTrooper` rows, including handlers.

For a shift with no stations:

- Existing global shift limits, organization limits, trooper limits, and handler limits continue to work as before.

This means an enabled stationed shift is intentionally controlled by the station totals rather than the older global/org limit pools.

---

## Standby Promotion

Standby promotion is station-scoped when a station is involved.

If a `GOING` trooper leaves a full station, the earliest `STAND_BY` trooper assigned to that same station is promoted to `GOING`.

If command staff raise a station's requested count, saving the shift promotes station standbys while the station has room.

If command staff lower a station's requested count below the current `GOING` count, saving the shift demotes the overflow signups to `STAND_BY`. The newest signups are demoted first so earlier signups keep priority.

Example:

| Station | Before | Change | After save |
|---|---:|---:|---:|
| Booth | 1/1 with one standby | Requested count raised to 2 | Earliest standby becomes `GOING`; station shows 2/2 |
| Booth | 3/3 | Requested count lowered to 2 | Newest `GOING` signup becomes `STAND_BY`; station shows 2/2 |

Standby troopers from other stations are not promoted into the opened station.

---

## Event Page Display

On the event page, each stationed shift shows a compact station summary in the shift header.

The summary displays:

```text
Station Name: current going count / requested count
```

The current count is based on visible `GOING` roster rows assigned to that station.

Each roster row also displays the trooper's station near costume/status details. Owners can edit their own station from the event page while the shift is open. Other users see the station as read-only text.

---

## Admin Roster Editing

The admin event roster page supports editing station assignments for trooper signups.

Station dropdowns are limited to stations from the signup's own shift. Submitting a station from a different shift is ignored.

Changing a trooper into a full station follows the same capacity rule as the public event page: the signup becomes or remains `STAND_BY`.

---

## Important Edge Cases

If a station exists, a new trooper signup must select one. If no station is selected, the signup is rejected with a validation message.

If a station is full, new signups are still stored, but with `STAND_BY` status.

If a station capacity is lowered below the current `GOING` count, overflow `GOING` rows are automatically demoted to `STAND_BY` on the next shift save. Demotion starts with the newest signup.

If a station capacity is raised, station standbys are promoted on the next shift save.

If a shift has no stations, all station behavior is bypassed and legacy signup limit behavior applies.

---

## Related Files

| Area | Files |
|---|---|
| Migrations | `database/migrations/2026_07_06_000002_create_event_shift_stations_table.php`, `database/migrations/2026_07_06_000003_add_event_shift_station_id_to_event_troopers_table.php` |
| Models | `app/Models/EventShiftStation.php`, `app/Models/EventShift.php`, `app/Models/EventTrooper.php` |
| Admin shift editor | `resources/views/pages/admin/events/shifts.blade.php`, `app/Http/Controllers/Admin/Events/UpdateShiftsSubmitController.php` |
| Admin roster editor | `resources/views/pages/admin/events/troopers.blade.php`, `app/Http/Controllers/Admin/Events/UpdateTroopersSubmitController.php` |
| Public signup/update | `app/Http/Controllers/Events/SignUpHtmxController.php`, `app/Http/Controllers/Events/SignUpUpdateHtmxController.php` |
| Event display | `resources/views/pages/events/inc/shift-header.blade.php`, `resources/views/pages/events/inc/trooper.blade.php` |
