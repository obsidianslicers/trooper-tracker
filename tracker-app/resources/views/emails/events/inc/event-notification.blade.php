<p>
    <b>{{ $event->name }}</b>
</p>
<p>
    {{ $event->organization->name }}
</p>
<p>
    <a href="{{ route('events.display', compact('event')) }}">
        {{ route('events.display', compact('event')) }}
    </a>
</p>
<ul>
    @foreach ($event_shifts as $event_shift)
        <li>
            <a href="{{ route('events.display', compact('event')) }}#shift-{{ $event_shift->id }}">
                {{ $event_shift->shift_starts_at->format('D') }} {{ $event_shift->short_time_display }}
            </a>
        </li>
    @endforeach
</ul>
<hr />