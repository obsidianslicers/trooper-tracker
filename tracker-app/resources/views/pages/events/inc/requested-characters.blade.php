<x-section-title>{{ __('events.Requested Characters') }}</x-section-title>
<p>
    {{ __('events.Number of Characters:') }}
    @if (!empty($event->requested_number_characters))
        {{ $event->requested_number_characters }}
    @else
        <span class="text-muted">
            {{ __('events.not specified') }}
        </span>
    @endif
</p>
<p>
    {{ __('events.Character Types:') }}
    @if (!empty($event->requested_character_types))
        {{ $event->requested_character_types }}
    @else
        <span class="text-muted">
            {{ __('events.not specified') }}
        </span>
    @endif
</p>