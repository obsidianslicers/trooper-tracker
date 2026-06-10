@extends('layouts.email')

@section('message')
    <p>
        Hello Moderators,
    </p>

    <p>
        <strong>{{ $trooper_request->trooper->display_name }}</strong> has submitted a
        request to join {{ $organization->name }}.
        @if($trooper_request->identifier && $trooper_request->identifier != $trooper_request->trooper->display_name)
            Their submitted identifier is: <strong>{{ $trooper_request->identifier }}</strong>.
        @endif
    </p>

    <p>
        When you have a moment between enforcing order and pretending the galaxy isn't on fire,
        please review this request on the
        <a href="{{ route('admin.troopers.approvals') }}">Approvals page</a>
        and decide their fate accordingly.
    </p>

    <p>
        Thank you for your ongoing efforts to keep membership records accurate,
        organized, and only mildly chaotic.
    </p>

    @include('emails.inc.signature')

@endsection