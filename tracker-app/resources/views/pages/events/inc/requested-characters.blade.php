<x-section-title>Requested Characters</x-section-title>
<p>
    Number of Characters: {{ $event->requested_number_characters ?? 'not specified' }}
</p>
<p>
    Character Types: {{ $event->requested_character_types ?? 'not specified' }}
</p>