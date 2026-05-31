@extends('layouts.email')

@section('message')
    <p>
        Hello Moderators,
    </p>

    <p>
        <strong>{{ $join_request->trooper->display_name }}</strong> has submitted a request to join your organization.
        @if($join_request->identifier && $join_request->identifier != $join_request->trooper->display_name)
            Their submitted identifier is: <strong>{{ $join_request->identifier }}</strong>.
        @endif
    </p>

    <p>
        Please review this request on the <a href="{{ route('admin.troopers.approvals') }}">Approvals page</a>
        and approve or deny it as appropriate.
    </p>

    <p>
        Thank you for helping keep membership records accurate.
    </p>

    @include('emails.inc.signature')

@endsection
