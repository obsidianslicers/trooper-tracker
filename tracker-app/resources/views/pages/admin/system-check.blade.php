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
</div>

@endsection
