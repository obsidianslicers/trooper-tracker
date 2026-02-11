@extends('layouts.base')

@section('page-title', 'Event Shift Complete')

@section('content')

<x-slim-container>
    @php($bg = $event_trooper->attended ? 'alert-success' : 'alert-danger')

    <div class="alert {{ $bg }} my-4">
        <h4 class="alert-heading">
            Shift Completed for <b>{{ $event_trooper->event_shift->event->name }}</b>
            <br />
            <span class="text-muted">
                {{ $event_trooper->event_shift->time_display }}
            </span>
        </h4>
        @if($event_trooper->event_shift->event->can_update_trooper_status)
            <p>
                {{ $message }}
            </p>
        @else
            <p>
                The event has been closed for some time now, please contact your Imperial Records Officer.
            </p>
        @endif
    </div>

</x-slim-container>

@endsection