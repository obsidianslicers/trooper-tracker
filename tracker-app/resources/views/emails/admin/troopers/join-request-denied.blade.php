@extends('layouts.email')

@section('message')
    <p>
        Trooper {{ $trooper->display_name }},
    </p>

    <p>
        Your request to join <strong>{{ $organization->name }}</strong> was not approved.
    </p>

    @if($denial_reason)
        <p>The reviewer provided the following feedback:</p>
        <blockquote>{{ $denial_reason }}</blockquote>
    @endif

    <p>
        You may submit a new request at any time by visiting your
        <a href="{{ route('account.club-memberships') }}">club memberships</a> page.
    </p>

    @include('emails.inc.signature')

@endsection
