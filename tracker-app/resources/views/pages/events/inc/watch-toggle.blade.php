<div id="event-watch-toggle">
    <button class="btn btn-sm {{ $is_watching ? 'btn-secondary' : 'btn-outline-secondary' }}"
            hx-post="{{ route('events.toggle-watch-htmx', compact('event')) }}"
            hx-target="#event-watch-toggle"
            hx-swap="outerHTML">
        <i class="fa fa-fw {{ $is_watching ? 'fa-bell' : 'fa-bell-slash' }}"></i>
        {{ $is_watching ? 'Watching' : 'Watch Event' }}
    </button>
</div>
