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

                <x-table class="mt-3">
                    <thead>
                        <tr>
                            <th>
                                Trooper Name
                            </th>
                            <th>
                                Costume /
                                <span class="text-muted">Backup</span>
                            </th>
                            <th>
                                Status
                            </th>
                        </tr>
                    </thead>
                    @foreach ($event_shifts as $event_shift)
                        <tr>
                            <td colspan="3">
                                {{ $event_shift->time_display }}
                            </td>
                        </tr>
                        @foreach($event_shift->event_troopers as $event_trooper)
                            <tr>
                                <td>
                                    <i class="fa fa-fw {{ $event_trooper->attended ? 'fa-check text-success' : 'fa-times text-danger' }} me-2"></i>
                                    {{ $event_trooper->trooper->name }}
                                </td>
                                <td>
                                    {{ $event_trooper->organization_costume->full_name ?? 'N/A' }}
                                    <br />
                                    <span class="text-muted">{{ $event_trooper->backup_costume->full_name ?? 'N/A' }}</span>
                                </td>
                                <td>
                                    <x-input-select :property="'troopers.' . $event_trooper->id . '.status'"
                                                    :options="\App\Enums\EventTrooperStatus::toArray()"
                                                    :value="$event_trooper->status->value"
                                                    class="form-select-sm" />
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </x-table>

                <x-submit-container>
                    @if($event->is_active)
                        <x-submit-button>Update</x-submit-button>
                    @endif
                    <x-link-button-cancel :url="route('admin.events.troopers', compact('event'))" />
                </x-submit-container>

            </form>
        </x-card>
    </x-slim-container>

@endsection