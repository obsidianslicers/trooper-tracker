todo:   awards down to trooper assignments
todo:   events for full admin work
todo:   club/organization identifiers
        - pull/sync from clubs (http/json/googlesheets)
        - auto verify club identifiers on registration
todo:   refactor ['name'=>$name] to compact('name') in controllers
todo:   ensure all columns are MODEL::COLUMN_NAME
bug:    seed trooper and pull other clubs (only 501 is seeding)
        - (ie) if has mandoid - then need to set is_member=true on Mandalorians
bug:    dashboard (broken)
        - trooper breakdown by organization
        - trooper breakdown by costume
        - volunteer hours
        - direct and indirect donations
todo:   queue emails and background work
todo:   trooper admin page (tabs)
        - memberships
todo:   trooper table add setup_complete_at
        - if NULL need middlware to advance to setup page (or from login page and not support the remember_me)
            pick other club/units
            fix email
todo:   login - user picks between standealone & forum
todo:   filter home events
        - by hosting organization
        - by name using JS
        map view
        calendar view
note:   make better use of domain-partials (event_shifts)
note:   ensure consistent controller naming conventions "Update|Create|List(or plural Models)"Controller
todo:   test all of events
todo:   copy event
