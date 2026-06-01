<x-section-title>Requested Characters</x-section-title>
@if($event->requested_number_characters && $event->requested_number_characters > 0)
    <p>
        Number of Characters: {{ $event->requested_number_characters }}
    </p>
@endif
<p>
    Character Types: {{ $event->requested_character_types ?? 'not specified' }}
</p>