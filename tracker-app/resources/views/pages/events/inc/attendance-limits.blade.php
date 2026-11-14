<x-section-title>Trooper Quotas</x-section-title>
@php
    $open_clubs = $event->organizations->filter(fn ($organization) => $organization->pivot->can_attend ?? false);
    $closed_clubs = $event->organizations->reject(fn ($organization) => $organization->pivot->can_attend ?? false);
    $unlimited = $open_clubs->isNotEmpty() && $open_clubs->every(fn ($organization) => $organization->pivot->troopers_allowed === null
        && $organization->pivot->handlers_allowed === null);
@endphp
@if($unlimited)
    {{-- the per-club list would only repeat "a lot / a lot" --}}
    <p class="mb-1">No limits</p>
    @if($closed_clubs->isNotEmpty())
        <p class="small text-muted">
            Not open to {{ $closed_clubs->pluck('name')->join(', ', ' or ') }}
        </p>
    @endif
@else
    <ul class="list-group list-group-flush">
        <li class="list-group-item fw-bold">
            Organization
            <span class="float-end">
                <span class="d-none d-md-inline">
                    Troopers / Handlers
                </span>
                <span class="d-inline d-md-none">
                    Trp / Hnd
                </span>
            </span>
        </li>
        @foreach($event->organizations as $organization)
            <li class="list-group-item bg-transparent border-bottom">
                <x-yes-no class="me-2"
                          :value="$organization->pivot->can_attend ?? false" />
                {{ $organization->name }}
                @if($organization->pivot->can_attend)
                    <span class="float-end">
                        <x-number-format :value="$organization->pivot->troopers_allowed"
                                         :nulldisplay="'a lot'" />
                        /
                        <x-number-format :value="$organization->pivot->handlers_allowed"
                                         :nulldisplay="'a lot'" />
                    </span>
                @endif
            </li>
        @endforeach
    </ul>
@endif
