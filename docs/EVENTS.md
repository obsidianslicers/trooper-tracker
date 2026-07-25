# Events

## Event Types

Events are categorized by type for reporting and filtering. **Type does not affect business logic** — all 12 types share identical signup, capacity, and status-transition behavior.

| Enum | DB Value | Description |
|---|---|---|
| `REGULAR` | `regular` | Standard costuming appearance |
| `ARMOR_PARTY` | `armorparty` | Social armor build/maintenance gathering |
| `CHARITY` | `charity` | Charitable fundraising event |
| `PUBLIC_RELATIONS` | `publicrelations` | PR or promotional appearance |
| `DISNEY` | `disney` | Disney-related appearance |
| `LUCAS_FILM_LIMITED` | `lucasfilm` | Official Lucasfilm/Star Wars event |
| `CONVENTION` | `convention` | Convention or expo |
| `HOSPITAL` | `hospital` | Hospital visit or patient interaction |
| `WEDDING` | `wedding` | Private wedding appearance |
| `BIRTHDAY_PARTY` | `birthdayparty` | Private birthday party |
| `VIRTUAL_TROOP` | `virtualtroop` | Online/remote virtual event |
| `OTHER` | `other` | Anything else |

**Source:** `app/Enums/EventType.php`

---

## Event Status Lifecycle

An event moves through statuses as it progresses from creation to completion.

```mermaid
stateDiagram-v2
    [*] --> DRAFT : Event created

    DRAFT --> OPEN : Published for sign-ups
    DRAFT --> MANUAL_SELECTION : Published, approval required
    DRAFT --> SIGN_UP_LOCKED : Published, no new sign-ups

    OPEN --> MANUAL_SELECTION : Switch to approval mode
    OPEN --> SIGN_UP_LOCKED : Lock sign-ups
    MANUAL_SELECTION --> OPEN : Open to all
    MANUAL_SELECTION --> SIGN_UP_LOCKED : Lock sign-ups
    SIGN_UP_LOCKED --> OPEN : Re-open sign-ups
    SIGN_UP_LOCKED --> MANUAL_SELECTION : Switch to approval mode

    OPEN --> CLOSED : Event concluded
    MANUAL_SELECTION --> CLOSED : Event concluded
    SIGN_UP_LOCKED --> CLOSED : Event concluded
    DRAFT --> CLOSED : Event concluded

    OPEN --> CANCELLED : Event cancelled
    MANUAL_SELECTION --> CANCELLED : Event cancelled
    SIGN_UP_LOCKED --> CANCELLED : Event cancelled
    DRAFT --> CANCELLED : Event cancelled

    CLOSED --> [*]
    CANCELLED --> [*]
```

### Status Descriptions

| Status | DB Value | `is_open` | `is_active` | Description |
|---|---|---|---|---|
| `DRAFT` | `draft` | No | Yes | Created but not yet visible for sign-ups |
| `OPEN` | `open` | Yes | Yes | Accepting sign-ups normally |
| `MANUAL_SELECTION` | `manualselection` | Yes | Yes | Sign-ups land as STAND_BY; admin approves each one |
| `SIGN_UP_LOCKED` | `locked` | No | Yes | No new sign-ups; existing roster unchanged |
| `CLOSED` | `closed` | No | No | Event has concluded |
| `CANCELLED` | `cancelled` | No | No | Event was cancelled |

### Side Effects on Transition

| Transition | Side Effects |
|---|---|
| DRAFT → OPEN / MANUAL_SELECTION / SIGN_UP_LOCKED | All shifts updated to match new status; `SendEventCreatedNotificationsJob` dispatched |
| Any → CANCELLED | All shifts set to CANCELLED; all active `EventTrooper` records set to CANCELLED; `SendEventCancelledNotificationsJob` dispatched |
| Capacity limits changed | `ReconcileEventRosterJob` dispatched to re-evaluate the roster |

**Source:** `app/Http/Controllers/Admin/Events/UpdateSubmitController.php:51-95`

---

## Signup Flow

When a trooper signs up for a shift, the system determines whether they are assigned **GOING** (confirmed) or **STAND_BY** (waiting).

```mermaid
flowchart TD
    A([Trooper submits sign-up]) --> B{Event status\n== MANUAL_SELECTION?}

    B -- Yes --> SB[Status = STAND_BY]
    B -- No --> C{Is trooper\na handler?}

    C -- Yes --> D{handlersMaxed?}
    D -- Yes --> SB
    D -- No --> E{org_id set AND\norgHandlersMaxed?}
    E -- Yes --> SB
    E -- No --> G[Status = GOING]

    C -- No --> H{troopersMaxed?}
    H -- Yes --> SB
    H -- No --> I{org_id set AND\norgTroopersMaxed?}
    I -- Yes --> SB
    I -- No --> G

    G --> N{Handler OR\ncostume decided?}
    N -- Yes --> J[Save EventTrooper record]
    N -- No --> P[Status = PENDING]
    P --> J
    SB --> J
    J --> K{Added by\nanother trooper?}
    K -- Yes --> L[Dispatch CreateTrooperFriendshipJob]
    K -- No --> M([Send TrooperSignedUpNotification])
    L --> M
```

A costume decision means the trooper either picked a real costume or explicitly selected "attending without a costume" from the costume dropdown — see [Costume Decision Requirement](#costume-decision-requirement). The initial sign-up form itself never collects a costume; `PENDING` troopers make this decision afterward from their roster row.

**Source:** `app/Features/Events/Commands/SignUpEventTrooperCommandHandler.php`

---

## Standby Promotion Flow

When a confirmed (GOING) slot opens — due to cancellation, a role switch, or a reconcile run — the system promotes the next eligible STAND_BY trooper.

```mermaid
flowchart TD
    A([GOING slot opens]) --> B{org_id known\nfor departing trooper?}

    B -- Yes --> C["Find earliest STAND_BY with:\n• same role (handler/trooper)\n• same org_id OR\n  costume resolves to same org"]
    C --> D{Found?}
    D -- Yes --> PROMOTE

    D -- No --> E{global_was_full == true\nOR no org context?}
    B -- No --> E

    E -- Yes --> F["Find earliest STAND_BY with:\n• same role (any org)"]
    F --> G{Found?}
    G -- Yes --> PROMOTE
    G -- No --> NONE([No promotion])
    E -- No --> NONE

    PROMOTE([Promote to GOING\nSend TrooperPromotedToGoingNotification])
```

**Org priority:** When a trooper held an org-limited slot, the system first offers that slot to the next STAND_BY from the same org (by `organization_id` or by costume org inference). Only if no same-org STAND_BY exists — or if global capacity was also full — does it consider troopers from any org.

**Costume decision required:** A `STAND_BY` candidate is only eligible for promotion if they're a handler or have already decided on a costume (`EventTrooper::needsCostumeBeforeGoing()` is `false`). Candidates who haven't made that decision are skipped in favor of the next eligible candidate in the same pool — they remain on `STAND_BY` and can be promoted later once they decide, or picked up by a subsequent promotion run.

**Source:** `app/Features/Events/Commands/PromoteNextInLineEventTrooperCommandHandler.php`

---

## Trooper Attendance Status

Once signed up, a trooper's attendance is tracked by `EventTrooperStatus`.

| Status | DB Value | Set By | Meaning |
|---|---|---|---|
| `NONE` | `none` | System | Default; no status assigned |
| `GOING` | `going` | System / Trooper | Confirmed attendance |
| `STAND_BY` | `standby` | System | Waitlisted; promoted when a slot opens |
| `TENTATIVE` | `tentative` | Trooper | Trooper is unsure; only available on non-limited events |
| `PENDING` | `pending` | System | Signed up, but a non-handler trooper hasn't yet decided on a costume — see [Costume Decision Requirement](#costume-decision-requirement) |
| `ATTENDED` | `attended` | Admin | Trooper showed up and attended |
| `CANCELLED` | `cancelled` | Trooper / System | Trooper withdrew or event was cancelled |
| `NOT_PICKED` | `notpicked` | Admin | Not selected for a limited/manual-selection event |
| `NO_SHOW` | `noshow` | Admin | Confirmed GOING but did not appear |
| `UNABLE_TO_ATTEND` | `unabletoattend` | Admin | Could not attend for external reasons |

**Source:** `app/Enums/EventTrooperStatus.php`

---

## Costume Decision Requirement

A non-handler trooper cannot hold `GOING` status until they've decided on a costume. Handlers (`is_handler = true`, from a handler-type costume or the trooper's membership role) are exempt and skip this entirely.

`EventTrooper::needsCostumeBeforeGoing()` is the single rule:

```php
return !$this->is_handler && $this->costume_id === null && !$this->attending_without_costume;
```

A costume decision is one of two mutually exclusive choices, made from the costume dropdown on the trooper's roster row:

- Pick a real costume (`costume_id` set, `attending_without_costume = false`).
- Select "Attending without a costume" from the dropdown (`costume_id = null`, `attending_without_costume = true`) — for roles that are legitimately costume-less without being a Handler-type costume.

Leaving both undecided is what keeps (or puts) a trooper on `PENDING` instead of `GOING`.

**Where it's enforced:**

| Path | Behavior |
|---|---|
| Initial sign-up (`SignUpEventTrooperCommandHandler`) | Would-be `GOING` is downgraded to `PENDING` when undecided. Capacity-driven `STAND_BY` is unaffected. |
| Status change / manual-selection approval (`SignUpUpdateHtmxController`, `status` branch) | Requesting `GOING` while undecided is silently saved as `PENDING` instead. |
| Re-sign-up (`SignUpUpdateHtmxController`, `resign_up` branch) | Same downgrade as initial sign-up. |
| Stand-by promotion (`PromoteNextInLineEventTrooperCommandHandler`) | Undecided candidates are skipped — see [Standby Promotion Flow](#standby-promotion-flow). |
| Costume dropdown submit (`SignUpUpdateHtmxController`, `costume_id` branch, via `resolveCostumeDecision()`) | Deciding while `PENDING` promotes to `GOING` (or `STAND_BY` if capacity is now full); clearing the decision while `GOING` demotes back to `PENDING`. |

**UI:** The costume `<select>` on `resources/views/pages/events/inc/trooper.blade.php` includes a `none` sentinel option ("Attending without a costume") ahead of the real costume list. A `PENDING` trooper sees a hint prompting them to make a choice instead of the normal status dropdown.

**Source:** `app/Models/EventTrooper.php` (`needsCostumeBeforeGoing()`), `app/Http/Controllers/Events/SignUpUpdateHtmxController.php` (`resolveCostumeDecision()`), `database/migrations/2026_07_24_160615_add_attending_without_costume_to_event_troopers_table.php`

---

## Capacity Enforcement

Capacity is enforced at two levels. Both are checked on every sign-up.

### Global Limits (per event)

Stored on the `events` table:

| Column | Controls |
|---|---|
| `troopers_allowed` | Max total troopers per shift |
| `handlers_allowed` | Max total handlers per shift |
| `shifts_allowed` | Max shifts a single trooper can join on this event |
| `friends_allowed` | Max friends a trooper can add to a shift |
| `guests_allowed` | Max guests a trooper can add to a shift |

`NULL` means unlimited.

### Per-Organization Limits

Stored on the `event_organizations` table (one row per org invited to the event):

| Column | Controls |
|---|---|
| `troopers_allowed` | Max troopers from this org per shift |
| `handlers_allowed` | Max handlers from this org per shift |

`NULL` means no per-org cap for that role.

### Reconciliation

When an admin changes capacity limits on an active event, `ReconcileEventRosterJob` is dispatched. It re-evaluates every GOING and STAND_BY trooper on each shift, sorted by `signed_up_at` (earliest first), and promotes or demotes as needed to match the new limits. Affected troopers receive an email notification.

**Source:** `app/Jobs/ReconcileEventRosterJob.php`, `app/Models/EventShift.php`

---

## Notification Summary

| Trigger | Notification |
|---|---|
| Trooper signs up | `TrooperSignedUpNotification` |
| STAND_BY promoted to GOING (cancellation/role change) | `TrooperPromotedToGoingNotification` |
| Admin approves a STAND_BY (MANUAL_SELECTION event) | `ManualSelectionApprovedNotification` |
| Admin demotes a trooper (MANUAL_SELECTION event) | `ManualSelectionStandByNotification` |
| Event transitions from DRAFT to any open state | `SendEventCreatedNotificationsJob` |
| Event cancelled | `SendEventCancelledNotificationsJob` |
| Reconcile promotes a trooper | `TrooperNextInLine` email |
| Reconcile demotes a trooper | `TrooperManualSelectionStandBy` email |
| Daily digest | `DailyEventNotification` |

---

## Shift Structure

Each event contains one or more **shifts** (`EventShift`). Troopers sign up for individual shifts, not the event itself. Capacity limits are enforced per shift.

```
Event
└── EventShift (e.g., "Morning", "Afternoon")
    └── EventTrooper (one per trooper per shift)
```

When an event's status changes, all of its shifts are updated to match. Shift-level capacity checks happen inside `EventShift::troopersMaxed()`, `handlersMaxed()`, and `orgTroopersMaxed()`.

**Source:** `app/Models/EventShift.php:145-215`
