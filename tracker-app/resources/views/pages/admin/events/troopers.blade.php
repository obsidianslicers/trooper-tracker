@extends('layouts.base')

@section('page-title', 'Organization Limits')

@section('content')

    @include('pages.admin.events.tabs', compact('event'))

    <x-slim-container>

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
                            @if($event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
                                <th>
                                    Approved By
                                </th>
                                <th>
                                    Approved At
                                </th>
                                <th>
                                    Action
                                </th>
                            @endif
                        </tr>
                    </thead>
                    @foreach ($event_shifts as $event_shift)
                        <tr>
                            <td colspan="{{ $event->status === \App\Enums\EventStatus::MANUAL_SELECTION ? '6' : '3' }}">
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
                                    @if($event_trooper->costume == null && $event_trooper->backup_costume == null)
                                        <i class="small text-muted">
                                            no costume selected
                                        </i>
                                    @endif
                                    @if($event_trooper->costume)
                                        {{ $event_trooper->costume->name }}
                                        <br />
                                        <i class="small text-muted">
                                            {{ $event_trooper->costume_organizations }}
                                        </i>
                                    @endif
                                    @if($event_trooper->backup_costume)
                                        <br />
                                        <i class="small text-muted">
                                            <i class="fa fa-fw fa-box-archive"></i>
                                            {{ $event_trooper->backup_costume->name }}
                                            <br />
                                            {{ $event_trooper->backup_costume_organizations }}
                                        </i>
                                    @endif
                                </td>
                                <td>
                                    <x-input-select :property="'troopers.' . $event_trooper->id . '.status'"
                                                    :options="\App\Enums\EventTrooperStatus::toArray()"
                                                    :value="$event_trooper->status->value"
                                                    class="form-select-sm" />
                                </td>
                                @if($event->status === \App\Enums\EventStatus::MANUAL_SELECTION)
                                    <td>
                                        @if($event_trooper->status === \App\Enums\EventTrooperStatus::GOING && $event_trooper->updated_by && $event_trooper->updated_id !== $event_trooper->trooper_id)
                                            {{ $event_trooper->updated_by->display_name }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($event_trooper->status === \App\Enums\EventTrooperStatus::GOING && $event_trooper->updated_at && $event_trooper->updated_id !== $event_trooper->trooper_id)
                                            {{ $event_trooper->updated_at->format('M j, Y g:ia') }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($event_trooper->status === \App\Enums\EventTrooperStatus::STAND_BY)
                                            <button type="submit"
                                                    class="btn btn-sm btn-success"
                                                    name="approve_trooper_ids[]"
                                                    value="{{ $event_trooper->id }}">
                                                Approve to Going
                                            </button>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                @endif
                            </tr>
                        @empty
                            <x-table-empty :colspan="$event->status === \App\Enums\EventStatus::MANUAL_SELECTION ? 6 : 3">
                                No troopers assigned to this shift.
                            </x-table-empty>
                        @endforelse
                    @endforeach
                </x-table>

                <x-submit-container>
                    @if(Auth::user()->is_administrator || $event->can_update_trooper_status)
                        <x-submit-button>Update</x-submit-button>
                    @endif
                    <x-link-button-cancel :url="route('admin.events.troopers', compact('event'))" />
                </x-submit-container>

            </form>
        </x-card>
    </x-slim-container>

@endsection