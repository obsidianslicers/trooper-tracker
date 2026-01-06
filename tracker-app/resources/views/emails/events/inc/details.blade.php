<p>
    Here are the critical details of your upcoming assignment:
</p>
<ul>
    <li><b>Venue:</b> {{ $event->name }}</li>
    <li><b>Location:</b>
        <a href="https://www.google.com/maps/search/?api=1&query={{ $event->venue_address }}"
           target="_blank">
            {{ $event->venue_address }}
        </a>
    </li>
    <li><b>Shift:</b> {{ $event_shift->shift_starts_at->format('F j, Y g:i A') }} - {{ $event_shift->shift_ends_at->format('g:i A') }}</li>
    <li>
        <b>Add to Calendar:</b>
        <a target="_blank"
           title="Create Google Calendar Event"
           href="{{ $link->google() }}">
            Google
        </a>
        &nbsp;|&nbsp;
        <a target="_blank"
           title="Create Yahoo Calendar Event"
           href="{{ $link->yahoo() }}">
            Yahoo
        </a>
        &nbsp;|&nbsp;
        <a target="_blank"
           title="Create Outlook Calendar Event"
           href="{{ $link->webOutlook() }}">
            Outlook
        </a>
        &nbsp;|&nbsp;
        <a target="_blank"
           title="Download ICS File"
           href="{{ $link->ics() }}">
            Download ICS File
        </a>
    </li>
</ul>
<p>
    By signing up for this mission, you have agreed - implicitly, explicitly, and possibly
    accidentally - to the following:
</p>
<ul>
    <li>Arrive on time, or at least convincingly blame hyperspace traffic.</li>
    <li>Maintain proper Imperial posture, even when small children poke your armor.</li>
    <li>Refrain from engaging in philosophical debates with Rebels. They always think they win.</li>
</ul>
<p>
    Your deployment details can be reviewed at any time on the
    <a href="{{ route('events.display', $event) }}#shift-{{ $event_shift->id }}">mission briefing page</a>.
    Study it carefully. The Emperor appreciates preparedness almost as much as he appreciates
    dramatic entrances.
</p>
<p>
    Should your circumstances change, you may update your status - though be warned: the Imperial
    paperwork droids <i>do not</i> appreciate indecisiveness.
</p>