@php
    $charity_shifts = $event->event_shifts->filter(fn($s) => $s->charity_name || $s->charity_direct_funds > 0 || $s->charity_indirect_funds > 0 || $s->charity_hours > 0 || $s->charity_notes);
@endphp

@if($charity_shifts->isNotEmpty())
    <div class="mt-3">
        <x-section-title>Charity</x-section-title>
        @foreach($charity_shifts as $shift)
            <div @if(!$loop->first) class="mt-2" @endif>
                @if($charity_shifts->count() > 1)
                    <p class="mb-1 text-muted small">{{ $shift->time_display }}</p>
                @endif
                @if($shift->charity_name)
                    <p class="mb-1"><strong>{{ $shift->charity_name }}</strong></p>
                @endif
                <p class="mb-1">Direct Funds: ${{ number_format($shift->charity_direct_funds) }}</p>
                <p class="mb-1">Indirect Funds: ${{ number_format($shift->charity_indirect_funds) }}</p>
                @if($shift->charity_hours)
                    <p class="mb-1">Hours: {{ $shift->charity_hours }}</p>
                @endif
                @if($shift->charity_notes)
                    <p class="mb-1">{{ $shift->charity_notes }}</p>
                @endif
            </div>
        @endforeach
    </div>
@endif
