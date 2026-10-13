@extends('layouts.email')

@section('message')
    <p>
        Hello {{ $requester->display_name }},
    </p>

    <p>
        Your permission request for <strong>{{ $trooper->display_name }}</strong> was
        <strong>{{ $is_approved ? 'approved' : 'denied' }}</strong>.
    </p>

    @include('emails.inc.permission-changes')

    @if(!$is_approved && $permission_request->denial_reason)
        <p>
            Reason: {{ $permission_request->denial_reason }}
        </p>
    @endif

    <p>
        You can see your requests on the
        <a href="{{ route('admin.troopers.permissions.request') }}">Request Permissions page</a>.
    </p>

    @include('emails.inc.signature')

@endsection
