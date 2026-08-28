@extends('layouts.email')

@section('message')
    <p>
        Esteemed Moderators,
    </p>

    <p>
        A previously denied recruit, <b>{{ $trooper->display_name }}</b>, has reviewed the Imperial
        directive and chosen to resubmit their application. Their updated credentials have been
        returned to your queue for further scrutiny.
    </p>

    <p>
        You may review their resubmission in the
        <a href="{{ route('admin.troopers.membership.approvals') }}">Approval Chamber</a>.
    </p>

    @include('emails.inc.signature')

@endsection