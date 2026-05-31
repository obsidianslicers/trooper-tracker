<div id="event-watch-toggle" class="btn-group">
    <button class="btn {{ $is_watching ? 'btn-secondary' : 'btn-outline-secondary' }}"
            hx-post="{{ route('events.toggle-watch-htmx', compact('event')) }}"
            hx-target="#event-watch-toggle"
            hx-swap="outerHTML"
            title="{{ $is_watching ? 'Watching' : 'Watch Event' }}">
        <i class="fa fa-fw {{ $is_watching ? 'fa-bell' : 'fa-bell-slash' }}"></i>
    </button>
</div>
