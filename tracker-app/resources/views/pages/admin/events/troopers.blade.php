@extends('layouts.base')

@section('page-title', 'Organization Limits')

@section('content')

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>
        @php($has_station_shifts = $event_shifts->contains(fn($shift) => $shift->usesStations()))
        @php($trooper_colspan = ($event->status === \App\Enums\EventStatus::MANUAL_SELECTION ? 7 : 5) + ($has_station_shifts ? 1 : 0))

        <x-card>
            <form method="POST"
                  novalidate="novalidate">
                @csrf

                <x-input-container>
                    <x-label>
                        Event:
                    </x-label>
                    <x-input-text :property="'event_name'"
                                  :disabled="true"
                                  :value="$event->name" />
                </x-input-container>

                <x-input-container>
                    <x-label>
                        Event Status:
                    </x-label>
                    <x-input-text :property="'event_status'"
                                  :disabled="true"
                                  :value="to_title($event->status->name)" />
                </x-input-container>

                @if($event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
                    <x-message>
                        Manual Selection Event: all signups begin as Stand By and require Command Staff approval to move to Going.
                    </x-message>
                @endif

                <x-table class="mt-3">
                    <thead>
                        <tr>
                            <th>
                                Trooper Name
                            </th>
                            <th>
                                Costume
                            </th>
                            <th>
                                Status
                            </th>
                            @if($has_station_shifts)
                                <th>
                                    Station
                                </th>
                            @endif
                            <th>
                                Credited Org
                            </th>
                            @if($event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
                                <th>
                                    Approved By
                                </th>
                                <th>
                                    Approved At
                                </th>
                            @endif
                            <th></th>
                        </tr>
                    </thead>
                    @foreach ($event_shifts as $event_shift)
                        <tr>
                            <td colspan="{{ $trooper_colspan }}">
                                {{ $event_shift->time_display }}
                            </td>
                        </tr>
                        @forelse($event_shift->event_troopers as $event_trooper)
                            <tr>
                                <td class="ps-4">
                                    <a href="{{ route('admin.troopers.profile', ['trooper' => $event_trooper->trooper_id]) }}">
                                        {{ $event_trooper->trooper->display_name }}
                                    </a>
                                </td>
                                <td>
                                    <x-input-select :property="'troopers.' . $event_trooper->id . '.costume_id'"
                                                    :options="$event_trooper->costume_options"
                                                    :value="$event_trooper->costume_id"
                                                    :placeholder="'-- No Costume --'"
                                                    class="form-select-sm"
                                                    hx-get="{{ route('admin.events.troopers.org-options', compact('event', 'event_trooper')) }}"
                                                    hx-vals="js:{costume_id: this.value}"
                                                    hx-target="#org-options-{{ $event_trooper->id }}"
                                                    hx-trigger="change" />
                                    @if($event_trooper->backup_costume)
                                        <i class="small text-muted d-block mt-1">
                                            <i class="fa fa-fw fa-box-archive"></i>
                                            {{ $event_trooper->backup_costume->name }}
                                        </i>
                                    @endif
                                </td>
                                <td>
                                    <x-input-select :property="'troopers.' . $event_trooper->id . '.status'"
                                                    :options="\App\Enums\EventTrooperStatus::toArray()"
                                                    :value="$event_trooper->status->value"
                                                    class="form-select-sm" />
                                </td>
                                @if($has_station_shifts)
                                    <td>
                                        @if($event_shift->usesStations())
                                            <x-input-select :property="'troopers.' . $event_trooper->id . '.event_shift_station_id'"
                                                            :options="$event_shift->event_shift_stations->pluck('name', 'id')->toArray()"
                                                            :value="$event_trooper->event_shift_station_id"
                                                            :placeholder="'-- Select Station --'"
                                                            class="form-select-sm" />
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td>
                                    <div id="org-options-{{ $event_trooper->id }}">
                                        @include('pages.admin.events.inc.trooper-org-options', [
                                            'org_options' => $event_trooper->org_options,
                                            'credited_ids' => $event_trooper->credited_checked_ids,
                                        ])
                                    </div>
                                </td>
                                @if($event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
                                    <td>
                                        @if($event_trooper->status === \App\Enums\EventTrooperStatus::GOING && $event_trooper->updated_by)
                                            {{ $event_trooper->updated_by->display_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($event_trooper->status === \App\Enums\EventTrooperStatus::GOING && $event_trooper->updated_at)
                                            {{ $event_trooper->updated_at->format('M j, Y g:ia') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td class="text-end">
                                    @if(Auth::user()->is_administrator || Auth::user()->isModeratorForOrganization($event->organization))
                                        <button type="button"
                                                class="btn btn-sm btn-link text-danger p-1"
                                                hx-post="{{ route('admin.events.troopers.remove', compact('event', 'event_trooper')) }}"
                                                hx-confirm="Remove {{ $event_trooper->trooper->display_name }} from the roster?"
                                                hx-trigger="click"
                                                title="Remove from roster">
                                            <i class="fa fw fa-times"></i>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-table-empty :colspan="$trooper_colspan">
                                No troopers assigned to this shift.
                            </x-table-empty>
                        @endforelse

                        @if($event_shift->event_guests->isNotEmpty())
                            <tr>
                                <td colspan="{{ $trooper_colspan }}"
                                    class="ps-4 text-muted small">
                                    Guests
                                </td>
                            </tr>
                        @endif
                        @foreach($event_shift->event_guests as $event_guest)
                            <tr>
                                <td class="ps-4">
                                    {{ $event_guest->name }}
                                    @if($event_guest->added_by_trooper)
                                        <br />
                                        <i class="small text-muted">
                                            Added by {{ $event_guest->added_by_trooper->display_name }}
                                        </i>
                                    @endif
                                </td>
                                <td>
                                    <i class="small text-muted">
                                        Guest
                                    </i>
                                </td>
                                <td>
                                    <x-input-select :property="'guests.' . $event_guest->id . '.status'"
                                                    :options="\App\Enums\EventGuestStatus::toArray()"
                                                    :value="$event_guest->status->value"
                                                    class="form-select-sm" />
                                </td>
                                @if($has_station_shifts)
                                    <td></td>
                                @endif
                                <td></td>
                                @if($event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
                                    <td>
                                        @if($event_guest->status === \App\Enums\EventGuestStatus::GOING && $event_guest->updated_by)
                                            {{ $event_guest->updated_by->display_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($event_guest->status === \App\Enums\EventGuestStatus::GOING && $event_guest->updated_at)
                                            {{ $event_guest->updated_at->format('M j, Y g:ia') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                                <td></td>
                            </tr>
                        @endforeach

                        @if(Auth::user()->is_administrator || Auth::user()->isModeratorForOrganization($event->organization))
                            <tr>
                                <td colspan="{{ $trooper_colspan }}"
                                    class="ps-4 py-2">
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary"
                                            hx-get="{{ route('pickers.trooper', [
                                                'property' => 'admin-add-' . $event_shift->id,
                                                'event' => 'admin-add-trooper-' . $event_shift->id,
                                            ]) }}"
                                            hx-target="#modal-trooper .modal-body"
                                            hx-trigger="click"
                                            data-bs-toggle="modal"
                                            data-bs-target="#modal-trooper">
                                        <i class="fa fa-fw fa-plus-circle me-1"></i>
                                        Add Trooper
                                    </button>
                                    <div id="admin-add-step2-{{ $event_shift->id }}"></div>
                                    <div class="d-none"
                                         hx-get="{{ route('admin.events.troopers.costume-picker', compact('event', 'event_shift')) }}"
                                         hx-vals="js:{trooper_id: event.detail.id}"
                                         hx-trigger="admin-add-trooper-{{ $event_shift->id }} from:document"
                                         hx-target="#admin-add-step2-{{ $event_shift->id }}"
                                         hx-swap="innerHTML">
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </x-table>

                <x-modal-picker :id="'modal-trooper'" :label="'Find a Trooper'" />

                <x-submit-container>
                    @if(Auth::user()->is_administrator || Auth::user()->isModeratorForOrganization($event->organization))
                        <x-submit-button>Update</x-submit-button>
                    @endif
                    <x-link-button-cancel :url="route('admin.events.troopers', compact('event'))" />
                </x-submit-container>

            </form>
        </x-card>
    </x-slim-container>

@endsection
