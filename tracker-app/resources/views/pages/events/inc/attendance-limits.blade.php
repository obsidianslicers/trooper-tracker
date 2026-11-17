<x-section-title>{{ __('events.Trooper Quotas') }}</x-section-title>
@php
    $open_clubs = $event->organizations->filter(fn ($organization) => $organization->pivot->can_attend ?? false);
    $closed_clubs = $event->organizations->reject(fn ($organization) => $organization->pivot->can_attend ?? false);
    $unlimited = $open_clubs->isNotEmpty() && $open_clubs->every(fn ($organization) => $organization->pivot->troopers_allowed === null
        && $organization->pivot->handlers_allowed === null);
@endphp
@if($unlimited)
    {{-- the per-club list would only repeat "a lot / a lot" --}}
    <p class="mb-1">{{ __('events.No limits') }}</p>
    @if($closed_clubs->isNotEmpty())
        <p class="small text-muted">
            {{ __('events.Not open to :clubs', ['clubs' => $closed_clubs->pluck('name')->join(', ', ' '.__('events.or').' ')]) }}
        </p>
    @endif
@else
    <ul class="list-group list-group-flush">
        <li class="list-group-item fw-bold">
            {{ __('events.Organization') }}
            <span class="float-end">
                <span class="d-none d-md-inline">
                    {{ __('events.Troopers / Handlers') }}
                </span>
                <span class="d-inline d-md-none">
                    {{ __('events.Trp / Hnd') }}
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
