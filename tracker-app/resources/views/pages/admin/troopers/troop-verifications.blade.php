@extends('layouts.base')

@section('page-title', 'Troop Verifications')

@section('content')

    <x-slim-container>

        <x-card>

            <p class="text-muted">
                These troopers need a moderator to confirm their troops. Approve each club
                that should get credit. A club you approve counts the troop right away.
            </p>

            <x-table>
                <thead>
                    <tr>
                        <th>Trooper</th>
                        <th>Troop</th>
                        <th>Costume</th>
                        <th>Club credit</th>
                    </tr>
                </thead>
                @forelse ($event_troopers as $event_trooper)
                    <tr>
                        <td>
                            <a href="{{ route('admin.troopers.profile', ['trooper' => $event_trooper->trooper, 'tab' => 'events']) }}">
                                {{ $event_trooper->trooper->display_name }}
                            </a>
                        </td>
                        <td>
                            <a href="{{ route('events.display', ['event' => $event_trooper->event_shift?->event]) }}">
                                {{ $event_trooper->event_shift?->event?->name }}
                            </a>
                            <br />
                            <span class="text-muted small">
                                {{ $event_trooper->event_shift?->shift_starts_at?->format('M j, Y') }}
                            </span>
                        </td>
                        <td>
                            @if ($event_trooper->costume)
                                {{ $event_trooper->costume->name }}
                            @elseif ($event_trooper->is_attending_without_costume)
                                <span class="text-muted">No costume</span>
                            @else
                                <span class="text-muted">None picked</span>
                            @endif
                        </td>
                        <td class="text-nowrap">
                            @foreach ($event_trooper->reviewable_organizations as $organization)
                                <div class="mb-1">
                                    <span class="me-2">{{ $organization->name }}</span>
                                    <form method="POST"
                                          class="d-inline"
                                          action="{{ route('admin.troopers.troop-verifications.approve', ['event_trooper' => $event_trooper, 'organization' => $organization]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-success">
                                            Approve
                                        </button>
                                    </form>
                                    <form method="POST"
                                          class="d-inline"
                                          action="{{ route('admin.troopers.troop-verifications.reject', ['event_trooper' => $event_trooper, 'organization' => $organization]) }}">
                                        @csrf
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger">
                                            Remove
                                        </button>
                                    </form>
                                </div>
                            @endforeach
                        </td>
                    </tr>
                @empty
                    <x-table-empty :colspan="4">
                        No troops are waiting for verification.
                    </x-table-empty>
                @endforelse
            </x-table>

        </x-card>

    </x-slim-container>

@endsection
