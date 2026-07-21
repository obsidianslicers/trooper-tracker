# Trooper Signup Limits

This document captures the current, code-backed scenarios where a trooper can be limited during event shift signup in Troop Tracker. It stays close to the code and does not infer missing requirements.

## Primary Self-Signup Gate

The active self-signup check is `EventShift::canSignUp(Trooper $trooper)` in [`tracker-app/app/Models/EventShift.php`](../tracker-app/app/Models/EventShift.php).

A trooper can sign up when all of the following are true:

1. The shift is open.
2. The event requires mission brief acknowledgement, and the trooper has acknowledged it.
3. The trooper is a minor, and the event allows minors to attend.
4. The trooper is a minor, and a guardian is already signed up for the same shift.
5. The trooper has not already signed up for the shift.
6. If the event has a `shifts_allowed` limit, the trooper's signed-up shift count for that event stays below the limit.

If any of those checks fail, the self-signup is blocked.

## Mission Brief Requirement

Mission brief acknowledgement is enforced through `Event::hasMissionBriefAcknowledgementFor(Trooper $trooper)` in [`tracker-app/app/Models/Event.php`](../tracker-app/app/Models/Event.php).

When `tt_events.require_mission_brief_ack` is true, the trooper must already have a row in `tt_event_mission_acks` for that event before any of the following can proceed:

1. Self-signup for a shift.
2. Signing up a friend.
3. Signing up a guest.

The Blade UI also hides the signup controls until the mission brief is acknowledged, but the model methods still act as the source of truth.

## Minor Trooper Restrictions

Minor signup is limited by two additional checks inside `EventShift::canSignUp()`:

1. If the event has no associated organization that allows minors to attend, the minor cannot sign up.
2. If the minor is allowed to attend, the guardian must already be signed up for the same shift.

The guardian check is performed against the shift itself, not just the event.

## Capacity-Based Limits

The code uses different capacity checks depending on the type of signup:

1. Self-signup uses the event-level `shifts_allowed` limit, if one exists.
2. Handler signups use the handler capacity for the shift.
3. Non-handler signups use the trooper capacity for the shift.
4. If an organization is selected, the signup is also limited by the organization-specific capacity for that shift.

If a shift has stations, a station is also required and its capacity is checked alongside the limits above — a stationed signup must fit the station, event, and organization limits at the same time. See [Station Signups](STATION_SIGNUPS.md) for the station-specific rules, display behavior, and standby promotion flow.

When a signup cannot be completed because the trooper has reached the event’s shift limit, `SignUpHtmxController` returns the normal shift container plus an `X-Flash-Message` explaining that the maximum number of shift signups has been reached.

The actual status assigned after a successful submit is decided by `SignUpEventTrooperCommandHandler` in [`tracker-app/app/Features/Events/Commands/SignUpEventTrooperCommandHandler.php`](../tracker-app/app/Features/Events/Commands/SignUpEventTrooperCommandHandler.php):

1. Manual selection events always place the trooper on stand-by.
2. Handler signups fall back to stand-by when handler capacity is full.
3. Non-handler signups fall back to stand-by when trooper capacity is full.
4. If an organization is selected, organization capacity can also force stand-by.

## Signing Up Another Trooper

The HTMX signup controller accepts an optional `trooper_id` for moderator-driven signups in [`tracker-app/app/Http/Controllers/Events/SignUpHtmxController.php`](../tracker-app/app/Http/Controllers/Events/SignUpHtmxController.php).

That path still uses the same `EventShift::canSignUp()` rules listed above. The controller only switches which trooper is being signed up.

## Signing Up Friends and Guests

Friend and guest signups are separate code paths, but they reuse the same mission brief requirement and open-shift check.

The Blade UI only exposes these controls to adult troopers.

Friend signup is limited by `EventShift::canSignUpTrooper(Trooper $trooper)`:

1. The shift must be open.
2. The trooper must have mission brief acknowledgement when required.
3. The trooper must already be signed up and going.
4. The trooper must still have remaining friend slots when `friends_allowed` is set.

Guest signup is limited by `EventShift::canSignUpGuest(Trooper $trooper)`:

1. The shift must be open.
2. The trooper must have mission brief acknowledgement when required.
3. The trooper must already be signed up and going.
4. The trooper must still have remaining guest slots when `guests_allowed` is set.

The corresponding UI in [`tracker-app/resources/views/pages/events/inc/shift-add-trooper.blade.php`](../tracker-app/resources/views/pages/events/inc/shift-add-trooper.blade.php) also suppresses the add-trooper and add-guest controls unless the acting trooper is an adult and is already going, or a moderator is allowed to act on behalf of the event.

## Related Behavior

1. `EventShift::canSignUp()` is the active self-signup gate.
2. `Event::canSignUp()` exists only as a commented-out legacy method and is not the current decision point.
3. `SignUpUpdateHtmxController` applies separate rules for changing existing sign-up status, costume, resignation, and re-signup. Those are update rules, not initial signup rules.

## Practical Summary

A trooper can be prevented from signing up when the shift is closed, the mission brief has not been acknowledged, the trooper is a minor without the required guardian conditions, the trooper is already signed up, the event shift limit has been reached, or the selected organization/handler capacity is full.

Friend and guest additions have the same open-shift and mission-brief prerequisites, plus their own remaining-slot limits.
