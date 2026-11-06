@extends('layouts.email')

@section('message')
    <p>
        Hello Moderators,
    </p>

    <p>
        <strong>{{ $trooper_name }}</strong> confirmed they attended
        <strong>{{ $event_name }}</strong>
        @if($costume_name)
            in <strong>{{ $costume_name }}</strong>
        @endif
        and asked for credit in {{ implode(', ', $club_names) }}.
        This troop needs an officer to verify it before it counts.
    </p>

    <p>
        Please check the troop, then approve or reject each club on the
        <a href="{{ route('admin.troopers.troop-verifications') }}">Troop Verifications</a> page.
    </p>

    @include('emails.inc.signature')

@endsection
