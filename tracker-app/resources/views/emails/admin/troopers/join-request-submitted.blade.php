@extends('layouts.email')

@section('message')
    <p>
        Esteemed Moderators,
    </p>
    <p>
        <strong>{{ $join_request->trooper->display_name }}</strong> has submitted a request
        to join <strong>{{ $join_request->organization->name }}</strong>.
        @if($join_request->identifier)
            Their submitted identifier is: <strong>{{ $join_request->identifier }}</strong>.
        @endif
    </p>
    <p>
        When you're ready, head to the
        <a href="{{ route('admin.troopers.approvals') }}">Approvals page</a>
        to review and approve or deny this request.
    </p>

    @include('emails.inc.signature')

@endsection
