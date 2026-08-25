@extends('layouts.base')

@section('page-title', 'System Check')

@section('content')

<div class="mt-2">
    <p class="text-muted">
        Verifies PHP requirements, server settings, and integrations needed for this
        install to run correctly.
    </p>

    @foreach($checks as $group => $results)
        <div class="card mb-3">
            <div class="card-header">
                {{ $group }}
            </div>
            <ul class="list-group list-group-flush">
                @foreach($results as $result)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span>
                            {{ $result->label }}
                            @if($result->detail)
                                <br>
                                <small class="text-muted">{{ $result->detail }}</small>
                            @endif
                        </span>
                        <span class="badge {{ $result->status->badgeClass() }}">
                            <i class="fa fa-fw fa-solid {{ $result->status->icon() }}"></i>
                            {{ ucfirst($result->status->value) }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach

    <div class="card mb-3">
        <div class="card-header">
            Recent Errors
        </div>
        @if($recent_errors->isEmpty())
            <div class="card-body text-muted">
                No recent error-level log entries found.
            </div>
        @else
            <ul class="list-group list-group-flush">
                @foreach($recent_errors as $index => $entry)
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <span class="badge {{ $entry->badgeClass() }} me-2">
                                {{ strtoupper($entry->level) }}
                            </span>
                            <small class="text-muted ms-auto">{{ $entry->logged_at }}</small>
                        </div>
                        <div class="mt-1">{{ $entry->summary() }}</div>
                        <a href="#" class="small"
                           data-bs-toggle="collapse"
                           data-bs-target="#error-detail-{{ $index }}">
                            View full error
                        </a>
                        <div id="error-detail-{{ $index }}" class="collapse mt-2">
                            <pre class="bg-dark text-light p-2 rounded small mb-0" style="white-space: pre-wrap;">{{ $entry->message }}</pre>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>

@endsection
