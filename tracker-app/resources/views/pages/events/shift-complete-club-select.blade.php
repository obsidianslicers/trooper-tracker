@extends('layouts.base')

@section('page-title', 'Select Clubs for Credit')

@section('content')

<x-slim-container>

    <div class="alert alert-info my-4">
        <h4 class="alert-heading">
            Shift Completed for <b>{{ $event_trooper->event_shift->event->name }}</b>
            <br />
            <span class="text-muted">
                {{ $event_trooper->event_shift->time_display }}
            </span>
        </h4>
        <p>
            Your costume is approved by multiple clubs you belong to.
            Select all clubs that should receive credit for this troop.
        </p>
    </div>

    <form method="POST"
          action="{{ route('events.shift-complete-club-select', ['event_trooper' => $event_trooper]) }}">
        @csrf

        <input type="hidden" name="encrypted_status" value="{{ $encrypted_status }}">

        <div class="mb-3">
            @foreach($organizations as $organization)
                <div class="form-check mb-2">
                    <input class="form-check-input"
                           type="checkbox"
                           name="organization_ids[]"
                           id="org_{{ $organization->id }}"
                           value="{{ $organization->id }}"
                           checked>
                    <label class="form-check-label" for="org_{{ $organization->id }}">
                        {{ $organization->name }}
                    </label>
                </div>
            @endforeach
        </div>

        <button type="submit" class="btn btn-success">
            Confirm Attendance
        </button>
    </form>

</x-slim-container>

@endsection
