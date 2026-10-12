@extends('layouts.email')

@section('message')
    <p>
        Hello Administrators,
    </p>

    <p>
        <strong>{{ $requester?->display_name ?? 'A moderator' }}</strong> has requested permission
        changes for <strong>{{ $trooper->display_name }}</strong>.
    </p>

    @if($permission_request->membership_role)
        <p>
            Requested role: <strong>{{ $permission_request->membership_role === \App\Enums\MembershipRole::MEMBER ? 'Regular trooper' : to_title($permission_request->membership_role->name) }}</strong>
        </p>
    @endif

    @if(!empty($permission_request->forum_groups_to_add))
        <p>
            Add forum groups: {{ collect($permission_request->forum_groups_to_add)->pluck('title')->implode(', ') }}
        </p>
    @endif

    @if(!empty($permission_request->forum_groups_to_remove))
        <p>
            Remove forum groups: {{ collect($permission_request->forum_groups_to_remove)->pluck('title')->implode(', ') }}
        </p>
    @endif

    @if($permission_request->note)
        <p>
            Note: {{ $permission_request->note }}
        </p>
    @endif

    <p>
        Please review this request on the
        <a href="{{ route('admin.troopers.permissions.approvals') }}">Permission Approvals page</a>.
    </p>

    @include('emails.inc.signature')

@endsection
