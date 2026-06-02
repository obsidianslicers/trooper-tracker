<x-section-title>Requested Characters</x-section-title>
<p>
    Number of Characters:
    @if (!empty($event->requested_number_characters))
        {{ $event->requested_number_characters }}
    @else
        <span class="text-muted">
            not specified
        </span>
    @endif
</p>
<p>
    Character Types:
    @if (!empty($event->requested_character_types))
        {{ $event->requested_character_types }}
    @else
        <span class="text-muted">
            not specified
        </span>
    @endif
</p>