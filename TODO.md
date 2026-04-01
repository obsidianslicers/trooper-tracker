### CI/CD

stan/pint - WIP
- app/helpers.php
- app/Bus
- # app/Http/Controllers
- # app/Http/Middleware
- # app/Http/Requests
- app/Jobs
- app/Mail
- app/Policies
- app/Rules

### REFACTOR CONVENTION
- template includes
  - path.inc.{page-name}-purpose.blade.php

### FIX
- Trooper Dashboard
- When closing a troop, you are unable to edit the status again

### MISC

- smash to single commit prior to beta 
- lock down main


## TROOPERS

### WORK IN PROGRESS
- Add better controls for adding/removing troopers on backend CS panel for events
- Manual selection event, similar to TT 1.0
- Download event roster to CSV (used my CS at troopers to help manage an event in person with physical paper)

## REPORTS
- Reports
  -  Search all troops by name, trooper attended, dates, TKID
  -  Troop Count Per Trooper (Report to get amount of troops attended by each trooper. You can do all or search by club)
  -  Donation Count Per Event (Donation Stats by date, can sort by charity event only and/or events with data only) this can be done as total or by club/squad
  -  Costume troop count between dates (Can see amount of troops done in a costume between certain dates)
  - A way to show a log of who is changing a troopers status on the backend for accountability (see notifications page on Troop Tracker 1.0)
  - I also had a tool to show active troopers that did not have a documented troop in the last year for census purposes.
- Statistics
  -  Costume used most
  -  Volunteers at Troops (Total count of troopers at all events)
  -  Direct Doantions Raised
  -  Indirect Donations Raised
  -  Counts of troop categories
  -  Total troops in Tracker
- bug:    dashboard (broken)
  - trooper breakdown by organization
  - trooper breakdown by costume
  - volunteer hours
  - direct and indirect donations

### QOL
- Make many names of events, troopers clickable and bring to the profile of the trooper
- Allow using Enter on forms

### COSTUMES
- Update/Delete Costumes from UI

## INTEGRATIONS
- todo:   club/organization identifiers
  - auto verify club identifiers on registration

## MISC
- Command Staff notifications (ie Bob or Jane's nth Troop), OR add to system notification for all, OR post to FB??


## EMAIL
- change email workflow? - for now lock it down
- password change workflow? forgot password workflow?

## Legacy Mobile App
- Refactor, add tests
- Does it respect event status to add guest, trooper? (Closed, draft, locked etc?) (double check)
