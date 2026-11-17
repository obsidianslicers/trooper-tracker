@extends('layouts.email')

@section('message')
    <p>
        Hello Administrators,
    </p>

    <p>
        <strong>{{ $requester?->display_name ?? 'A moderator' }}</strong> has requested permission
        changes for <strong>{{ $trooper->display_name }}</strong>.
    </p>

    @include(email_view('inc.permission-changes'))

    @if($permission_request->note)
        <p>
            Note: {{ $permission_request->note }}
        </p>
    @endif

    <p>
        Please review this request on the
        <a href="{{ route('admin.troopers.permissions.approvals') }}">Permission Approvals page</a>.
    </p>

    @include(email_view('inc.signature'))

@endsection
