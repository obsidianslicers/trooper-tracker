<x-section-title>Requested Characters</x-section-title>
<p>
    Number of Characters:
    @if ($event->requested_number_characters && $event->requested_number_characters > 0)
        {{ $event->requested_number_characters }}
    @else
        not specified
    @endif
</p>
<p>
    Character Types:
    @if ($event->requested_character_types && Str::length($event->requested_character_types) > 0)
        {{ $event->requested_character_types }}
    @else
        not specified
    @endif
</p>