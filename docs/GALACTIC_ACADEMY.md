# Galactic Academy — Guardian & Minor Accounts

Galactic Academy is a costuming organization for children and families. In TroopTracker it is
modeled as a standard `Organization` with `requires_guardian = true`. This flag drives a set of
registration requirements, runtime restrictions, and UI behaviors described below.

---

## Key Fields

| Field | Table | Purpose |
|---|---|---|
| `requires_guardian` | `tt_organizations` | Marks an organization as guardian-required (currently only Galactic Academy) |
| `date_of_birth` | `tt_troopers` | Determines `is_minor` (age < 18). Required at registration for guardian-required orgs |
| `guardian_id` | `tt_troopers` | FK to another trooper who is the parent/guardian. Required at registration for guardian-required orgs |

`is_minor` is a **derived** attribute — `date_of_birth` must be set and the trooper must be under 18.
If either field is missing the minor is treated as a regular adult account with no restrictions.

---

## Registration

When a trooper selects a `requires_guardian` organization during registration, both fields become
required (`RegisterRequest`):

- `date_of_birth` — must be between 13 and 17 years old
- `guardian_email` — must match an existing trooper account that does not itself have a `guardian_id`

`RegisterTrooperCommandHandler` looks up the guardian by email and sets `guardian_id` on the new
trooper. If the lookup fails the field is left null (no error thrown).

After registration, a `GuardianAwareness` email is sent to the guardian's address explaining that
they must co-attend events for the minor to sign up.

---

## Runtime Restrictions

These restrictions only apply when **both** `date_of_birth` is set and the trooper is under 18
(`is_minor = true`). If either field is missing, no restrictions are enforced.

### Event attendance (`EventShift`)

1. The event must include at least one organization with `requires_guardian = true`. A minor cannot
   sign up for any event that does not have a GA org attached.
2. The minor's guardian (`guardian_id`) must already be signed up for the **same shift**. The check
   is in `EventShift::isGuardianAttending()`.

### Trooper picker visibility (`GetTroopersForPickerQueryHandler`)

Troopers with `guardian_id` set are hidden from all other troopers in picker/search UIs. They are
only visible to the trooper whose `id` matches their `guardian_id`. This prevents minors from
appearing in event rosters, handler lists, etc. for unrelated staff.

---

## Guardian Account Effects

The guardian (parent) trooper gains:

- **Event co-attendance requirement** — must sign up for a shift before their minor can join it
- **Trooper picker visibility** — only they can see and select their minor in picker UIs
- **Account > Minors tab** — read-only list of minors linked to them via `guardian_id`
- **Awareness email** — sent at the minor's registration via `GuardianAwareness` mailable

The guardian does not gain any elevated permissions or moderation rights.

---

## Admin Management

Admins and moderators within scope can correct `date_of_birth` and `guardian_id` after registration
via the **Guardian tab** on a trooper's admin profile (`/admin/troopers/{id}/guardian`).

The tab is only shown when the trooper has an active membership in a `requires_guardian` organization
(`has_guardian_required_membership` accessor on `Trooper`).

The form accepts a **guardian email** and resolves it to `guardian_id` on save. Submitting an empty
email clears `guardian_id`.

> **Important:** Setting these fields correctly is not cosmetic. Without `date_of_birth` set to an
> under-18 value, all event attendance gates and trooper picker restrictions are silently bypassed.

---

## Relevant Files

| File | Purpose |
|---|---|
| `app/Models/Trooper.php` | `is_minor`, `is_adult`, `guardian()`, `minors()`, `has_guardian_required_membership` |
| `app/Models/Event.php` | `minorAllowedToAttend()`, `minorCannotAttend()` |
| `app/Models/EventShift.php` | Minor signup gate, `isGuardianAttending()` |
| `app/Features/Troopers/Queries/GetTroopersForPickerQueryHandler.php` | Hides minors from all but their guardian |
| `app/Http/Requests/Auth/RegisterRequest.php` | Enforces DOB + guardian email at registration |
| `app/Features/Troopers/Commands/RegisterTrooperCommandHandler.php` | Sets `guardian_id` and `date_of_birth` |
| `app/Http/Controllers/Auth/RegisterSubmitController.php` | Sends `GuardianAwareness` email |
| `app/Mail/Auth/GuardianAwareness.php` | Guardian awareness mailable |
| `app/Http/Controllers/Admin/Troopers/GuardianController.php` | Admin GET — guardian edit tab |
| `app/Http/Controllers/Admin/Troopers/GuardianSubmitController.php` | Admin POST — save DOB + guardian |
| `app/Http/Requests/Admin/Troopers/GuardianRequest.php` | Validation for admin guardian form |
| `app/Http/Controllers/Account/MinorsController.php` | Account > Minors read-only view |
