@extends('layouts.email')

@section('message')
    <p>
        Esteemed Administrators,
    </p>

    <p>
        {{ $count }} troop {{ $count === 1 ? 'verification has' : 'verifications have' }}
        waited more than 30 days for a moderator. Nothing expires on its own, so these
        troops won't count until someone reviews them.
    </p>

    <p>
        Please check the
        <a href="{{ route('admin.troopers.troop-verifications') }}">Troop Verifications</a>
        page, or ask the club's moderators to take a look.
    </p>

    @include('emails.inc.signature')

@endsection
