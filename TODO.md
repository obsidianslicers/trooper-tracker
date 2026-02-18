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

### MISC

- smash to single commit prior to beta 
- lock down main

### EVENT WIP COMPLETED
- Home Page Calendar/Map View
  - **IMPLEMENTED**
- Getting error on event e-mail parser submission
  - **IMPLEMENTATION**: server side handled via global exception handler
- Confirm troops, when a troop is finish, trooper needs to confirm attendance to get credit
  - **IMPLEMENTATION**: console job - event shift complete sends emails to request attendance credit
- A page of events that are completed (Charity liason team prefers this method (see stats&search page on TT 1.0))
  - **IMPLEMENTATION**: admin/events page has search/filters by name and status
- A way to close down the site temporarily with a message to allow command-staff to add events and such in the back end and open back up when they ready... maybe we can replace this with a post event NOW or AT THIS TIME IN THE FUTURE.
  - **IMPLEMENTATION**: events start in a DRAFT state and won't be visible until updated to OPEN or SIGNUP-LOCKED
- Make event e-mail parser optional, allow command-staff to fill form fields to create events. Allow command-staff to review form fields before posting e-mail data.
  - **IMPLEMENTATION**: event/creation allows for parser use or manually entry, or changes after the parse
  - **IMPLEMENTATION**: events can also be copied
- Charity information for events, charity liasons use tracker to track charity info.
  -  Direct Charity Raised ($)
  -  Indirect Charity Raised ($)
  -  Charity Name
  -  Charity Add Hours (This is used to calculate additional or less charity hours, otherwise the event duration is used)
  -  Charity Note (A note field to add misc, for example collected 15 Star Wars toys)
  - **IMPLEMENTED**
- Event Link Manager (A way to link events together so that a trooper can only sign up for a certain amount of troops that are linked together [comes into play for big events like MegaCon])
  - **IMPLEMENTED**

## TROOPERS

### WORK IN PROGRESS
- command-staff Statistics page show - Last API syncs with 501st. Rebel Legion API is dead.
- Profile page to show off acheivements, donations made, costumes (501st api)


### EVENT WIP COMPLETED
- command-staff Statistics page to show accounts with admin/mod permissions.
  - **IMPLEMENTATION**: Trooper admin page allows filtering of admin/mods
- Roster page to show all troopers in a club/squad. Ability to add troopers to a club / squad and assign additional club/squad information to their account.
  - **IMPLEMENTATION**: Thru trooper admin page

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

### CLUB-SYNC
- Edit Google Excel ID from UI
- Dashboard (view costumes and pictures)
- Join Date

### MOBILE API
- Begin converting old API to new API

### COSTUMES
- Update/Delete Costumes from UI

## INTEGRATIONS
- Xenforo tie in modules (When posting a event, cross post to Xenforo, use the comments on forum for tracker, ability to see forum link on event)
- Discord bot cross (When posting a event, cross post to Discord)
- todo:   club/organization identifiers
  - pull/sync from clubs (http/json/googlesheets)
  - auto verify club identifiers on registration

