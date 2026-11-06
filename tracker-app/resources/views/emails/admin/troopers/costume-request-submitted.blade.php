@extends('layouts.email')

@section('message')
    <p>
        Hello Moderators,
    </p>

    <p>
        <strong>{{ $trooper_name }}</strong> added <strong>{{ $costume_name }}</strong>
        for {{ implode(', ', $club_names) }}. It won't count toward troop credit in
        {{ count($club_names) === 1 ? 'that club' : 'those clubs' }} until an officer approves it.
    </p>

    <p>
        Please check that they're approved for this costume, then review it on the
        <a href="{{ route('admin.troopers.costume-approvals') }}">Costume Approvals</a> page.
    </p>

    @include('emails.inc.signature')

@endsection
