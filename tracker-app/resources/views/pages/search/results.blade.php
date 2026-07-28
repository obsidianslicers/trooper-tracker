@extends('layouts.base')

@section('page-title', $search_title)

@section('content')

    <div class="row mb-4">
        <div class="col-12">
            <form method="GET" action="{{ route('search.all') }}" class="d-flex gap-2">
                <input type="text"
                       name="q"
                       value="{{ $term }}"
                       placeholder="Search troopers, events, TK IDs..."
                       class="form-control"
                       autofocus />
                <select name="type" class="form-select" style="max-width: 160px;">
                    @foreach ($search_types as $value => $label)
                        <option value="{{ $value }}" @selected($type === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-primary text-nowrap">
                    <i class="fa fa-fw fa-search"></i> Search
                </button>
            </form>
        </div>
    </div>

    @if (strlen(trim($term)) < 2)
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-magnifying-glass fa-3x mb-3 d-block"></i>
            Enter at least 2 characters to search.
        </div>
    @elseif ($results === null)
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-magnifying-glass fa-3x mb-3 d-block"></i>
            No results.
        </div>
    @else

        {{-- Troopers --}}
        @if ($show_troopers)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-user-astronaut me-2"></i>
                    Troopers
                    <span class="badge bg-secondary ms-2">{{ $results['troopers']->count() }}</span>
                </div>
                @if ($results['troopers']->isEmpty())
                    <div class="card-body text-muted">No troopers found for &ldquo;{{ $term }}&rdquo;.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Name</th>
                                    <th>Memberships / IDs</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($results['troopers'] as $trooper)
                                    <tr>
                                        <td>
                                            <a href="{{ route('service-records.trooper', $trooper) }}" class="fw-bold text-decoration-none">
                                                {{ $trooper->display_name }}
                                            </a>
                                            @if (!empty($trooper->legal_name))
                                                <div class="text-muted small">
                                                    {{ $trooper->legal_name }}
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @foreach ($trooper->organizations as $org)
                                                @if ($org->pivot->identifier)
                                                    <span class="badge bg-secondary me-1" title="{{ $org->name }}">
                                                        {{ $org->identifier_display ?? $org->name }}: {{ $org->pivot->identifier }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($results['troopers']->count() === 25)
                        <div class="card-footer text-muted small">
                            Showing first 25 results &mdash;
                            <a href="{{ route('search.all', ['q' => $term, 'type' => 'troopers']) }}">search troopers only</a> for more.
                        </div>
                    @endif
                @endif
            </div>
        @endif

        {{-- Events --}}
        @if ($show_events)
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-dark text-white text-uppercase small fw-bold">
                    <i class="fa-solid fa-calendar-days me-2"></i>
                    Events / Troops
                    <span class="badge bg-secondary ms-2">{{ $results['events']->count() }}</span>
                </div>
                @if ($results['events']->isEmpty())
                    <div class="card-body text-muted">No events found for &ldquo;{{ $term }}&rdquo;.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Event</th>
                                    <th>Club</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($results['events'] as $event)
                                    <tr>
                                        <td>
                                            <a href="{{ route('events.display', $event) }}" class="fw-bold text-decoration-none">
                                                {{ $event->name }}
                                            </a>
                                            @if ($event->venue_address)
                                                <br /><span class="text-muted small">{{ $event->venue_address }}</span>
                                            @endif
                                        </td>
                                        <td class="text-muted small">{{ $event->organization->name ?? '—' }}</td>
                                        <td class="text-nowrap small">
                                            @if ($event->event_start)
                                                <x-date-format :value="$event->event_start" />
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-uppercase small">
                                                {{ $event->status->value }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($results['events']->count() === 25)
                        <div class="card-footer text-muted small">
                            Showing first 25 results &mdash;
                            <a href="{{ route('search.all', ['q' => $term, 'type' => 'events']) }}">search events only</a> for more.
                        </div>
                    @endif
                @endif
            </div>
        @endif

    @endif

@endsection
