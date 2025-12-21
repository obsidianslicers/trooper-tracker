todo:   awards down to trooper assignments
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
todo:   confirm attendance - how is it done today?
todo:   anything that's hooked into Xneforo
todo:   emails on signups, cancellations, stand-bys to going status for events
todo:   fix tests login/register
