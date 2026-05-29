@extends('layouts.email')

@section('message')
    <p>
        Esteemed Moderators,
    </p>

    <p>
        Another brave soul - <strong>{{ $join_request->trooper->display_name }}</strong> - has
        stepped forward and requested to join the Dark Empire. Yes, another one. The recruitment
        posters must be working.
        @if($join_request->identifier)
            Their submitted identifier is: <strong>{{ $join_request->identifier }}</strong>.
        @endif
    </p>

    <p>
        When your Imperial schedules permit, proceed to the <a href="{{ route('admin.troopers.approvals') }}">Approvals page</a>
        to render your judgment. Approve them, deny them, or simply bask in the fleeting power of bureaucratic authority.
    </p>

    <p>
        Your vigilance continues to keep the Empire running.
    </p>

    @include('emails.inc.signature')

@endsection